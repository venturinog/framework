<?php

/**
 * Per-browser-tab registry for the in-app Issabel tabs.
 *
 * The JSON file is the source of truth. Every operation takes an exclusive
 * flock so concurrent requests cannot lose updates.
 */
class issabelTabs
{
    const DEFAULT_STORE_DIR = '/var/lib/php/session-asterisk/tabs';

    private $sessionId;
    private $instanceId;
    private $storeDir;
    private $maxTabs;

    public function __construct($sessionId, $instanceId, $storeDir = null, $maxTabs = 8)
    {
        if (!is_string($sessionId) || $sessionId === '') {
            throw new InvalidArgumentException('A session ID is required');
        }
        if (!self::isValidInstanceId($instanceId)) {
            throw new InvalidArgumentException('Invalid tab instance ID');
        }

        $this->sessionId = $sessionId;
        $this->instanceId = $instanceId;
        $this->storeDir = ($storeDir === null) ? self::DEFAULT_STORE_DIR : $storeDir;
        $this->maxTabs = max(1, (int)$maxTabs);
    }

    public static function isValidInstanceId($instanceId)
    {
        return is_string($instanceId) && preg_match('/^[a-f0-9]{32}$/D', $instanceId) === 1;
    }

    /** Return the existing registry or atomically create its real Dashboard tab. */
    public function initialize()
    {
        return $this->transaction(function (&$data) {
            if (empty($data['tabs'])) {
                $data = $this->newRegistry();
            }
            return $data;
        });
    }

    public function getState()
    {
        return $this->transaction(function (&$data) {
            if (empty($data['tabs'])) {
                $data = $this->newRegistry();
            }
            return $data;
        });
    }

    public function find($key)
    {
        return $this->transaction(function (&$data) use ($key) {
            foreach ($data['tabs'] as $index => $tab) {
                if (isset($tab['key']) && $tab['key'] === $key) return $tab;
            }
            return null;
        });
    }

    public function normalizeKey($display, $extdisplay)
    {
        if (!is_string($display)) return null;
        $display = strtolower($display);
        $extdisplay = is_string($extdisplay) ? trim($extdisplay) : '';
        if ($display === '' || $display === 'index' || $display === 'dashboard') return 'pbx|landing';
        if (!preg_match('/^[a-zA-Z0-9_-]{1,80}$/D', $display)) return null;

        // FreePBX exposes extension editing through both screens.
        if ($display === 'users' && $extdisplay !== '') $display = 'extensions';
        if ($display === 'extensions' && strpos($extdisplay, '/') !== false) {
            $extdisplay = explode('/', $extdisplay, 2)[0];
        }
        if ($display === 'did' && strpos($extdisplay, '/') !== false) $extdisplay = '';
        if (strlen($extdisplay) > 100 || strpos($extdisplay, '|') !== false) return null;

        return $display.'|'.$extdisplay;
    }

    /** Create the intentionally distinct Dashboard landing tab used by `+`. */
    public function createDashboardLanding()
    {
        return $this->transaction(function (&$data) {
            if (count($data['tabs']) >= $this->maxTabs) {
                return array('created' => false, 'reason' => 'limit', 'state' => $data);
            }

            $id = (int)$data['nextTabId'];
            $tab = array(
                'id' => $id,
                'kind' => 'issabel',
                'key' => 'landing-dashboard-'.$id,
                'label' => 'Dashboard',
                'title' => 'Dashboard',
                'breadcrumb' => array(),
                'menuId' => 'dashboard',
                'src' => 'index.php?menu=dashboard&rawmode=iframe&iss_instance='.$this->instanceId.'&iss_tab='.$id,
                'dirty' => false,
                'loaded' => true,
            );
            $data['tabs'][] = $tab;
            $data['activeTab'] = $id;
            $data['nextTabId'] = $id + 1;

            return array('created' => true, 'tab' => $tab, 'state' => $data);
        });
    }

    /** Create a tab; duplicate identities focus the existing tab. */
    public function create(array $tab)
    {
        return $this->transaction(function (&$data) use ($tab) {
            $key = isset($tab['key']) ? $tab['key'] : '';
            foreach ($data['tabs'] as $existing) {
                if ($key !== '' && isset($existing['key']) && $existing['key'] === $key) {
                    $data['activeTab'] = $existing['id'];
                    return array('created' => false, 'tab' => $existing, 'state' => $data);
                }
            }

            if (count($data['tabs']) >= $this->maxTabs) {
                return array('created' => false, 'reason' => 'limit', 'state' => $data);
            }

            $id = (int)$data['nextTabId'];
            $tab = array_merge(array(
                'id' => $id,
                'kind' => 'issabel',
                'key' => '',
                'label' => '',
                'src' => '',
                'dirty' => false,
                'loaded' => false,
            ), $tab);
            $tab['id'] = $id;
            $data['tabs'][] = $tab;
            $data['activeTab'] = $id;
            $data['nextTabId'] = $id + 1;

            return array('created' => true, 'tab' => $tab, 'state' => $data);
        });
    }

    public function update($id, array $fields)
    {
        $allowed = array('kind', 'key', 'label', 'title', 'breadcrumb', 'menuId', 'src', 'dirty', 'loaded');
        $fields = array_intersect_key($fields, array_flip($allowed));

        return $this->transaction(function (&$data) use ($id, $fields) {
            foreach ($data['tabs'] as $index => $tab) {
                if ((int)$tab['id'] !== (int)$id) continue;

                if (isset($fields['key']) && $fields['key'] !== $tab['key']) {
                    foreach ($data['tabs'] as $other) {
                        if ((int)$other['id'] !== (int)$id && $other['key'] === $fields['key']) {
                            $data['activeTab'] = $other['id'];
                            return array('updated' => false, 'duplicate' => $other, 'state' => $data);
                        }
                    }
                }

                $data['tabs'][$index] = array_merge($tab, $fields);
                return array('updated' => true, 'tab' => $data['tabs'][$index], 'state' => $data);
            }
            return array('updated' => false, 'reason' => 'not_found', 'state' => $data);
        });
    }

    public function setActive($id)
    {
        return $this->transaction(function (&$data) use ($id) {
            foreach ($data['tabs'] as $index => $tab) {
                if ((int)$tab['id'] === (int)$id) {
                    $data['activeTab'] = (int)$id;
                    $data['tabs'][$index]['loaded'] = true;
                    return $data;
                }
            }
            return null;
        });
    }

    public function close($id)
    {
        return $this->transaction(function (&$data) use ($id) {
            if (count($data['tabs']) <= 1) {
                return array('closed' => false, 'reason' => 'last_tab', 'state' => $data);
            }

            foreach ($data['tabs'] as $index => $tab) {
                if ((int)$tab['id'] !== (int)$id) continue;
                array_splice($data['tabs'], $index, 1);
                if ((int)$data['activeTab'] === (int)$id) {
                    $neighbor = min($index, count($data['tabs']) - 1);
                    $data['activeTab'] = (int)$data['tabs'][$neighbor]['id'];
                }
                return array('closed' => true, 'state' => $data);
            }

            return array('closed' => false, 'reason' => 'not_found', 'state' => $data);
        });
    }

    public function jsonForClient()
    {
        $state = $this->getState();
        return array(
            'tabs' => $state['tabs'],
            'activeTab' => $state['activeTab'],
        );
    }

    private function newRegistry()
    {
        return array(
            'instanceId' => $this->instanceId,
            'activeTab' => 1,
            'nextTabId' => 2,
            'tabs' => array(array(
                'id' => 1,
                'kind' => 'issabel',
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'title' => 'Dashboard',
                'breadcrumb' => array(),
                'menuId' => 'dashboard',
                'src' => 'index.php?menu=dashboard&rawmode=iframe&iss_instance='.$this->instanceId.'&iss_tab=1',
                'dirty' => false,
                'loaded' => true,
            )),
        );
    }

    private function filepath()
    {
        $sessionKey = hash('sha256', $this->sessionId);
        return rtrim($this->storeDir, '/').'/'.$sessionKey.'_'.$this->instanceId.'.json';
    }

    /**
     * Read, modify and write under one lock. Even reads refresh file mtime,
     * so tmpfiles cleanup does not remove an active registry.
     */
    private function transaction(callable $callback)
    {
        $path = $this->filepath();
        $fp = @fopen($path, 'c+');
        if ($fp === false) throw new RuntimeException('Cannot open tab registry');
        @chmod($path, 0640);
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException('Cannot lock tab registry');
        }

        try {
            rewind($fp);
            $raw = stream_get_contents($fp);
            $data = ($raw === false || $raw === '') ? null : json_decode($raw, true);
            if (!is_array($data) || !isset($data['instanceId'], $data['tabs']) ||
                $data['instanceId'] !== $this->instanceId || !is_array($data['tabs'])) {
                $data = $this->newRegistry();
            }

            if (!isset($data['activeTab'], $data['nextTabId'])) $data = $this->newRegistry();
            $result = $callback($data);

            $json = json_encode($data);
            if ($json === false) throw new RuntimeException('Cannot encode tab registry');
            rewind($fp);
            if (!ftruncate($fp, 0) || fwrite($fp, $json) === false || !fflush($fp)) {
                throw new RuntimeException('Cannot write tab registry');
            }

            return $result;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
