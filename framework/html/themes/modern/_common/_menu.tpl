{*
  _menu.tpl - tema "Modern" (Issabel 6)
  Renderiza el sidebar + topbar + breadcrumb y ABRE el bloque .iss-main
  y .iss-content (que cierra _common/index.tpl).
*}
<input type="hidden" id="lblRegisterCm"   value="{$lblRegisterCm}" />
<input type="hidden" id="lblRegisteredCm" value="{$lblRegisteredCm}" />
<input type="hidden" id="userMenuColor"   value="{$MENU_COLOR}" />
<input type="hidden" id="issabel_theme_name" value="{$THEMENAME}" />
<input type="hidden" id="issabel_framework_module_id" value="{$ISSABEL_MODULE_ID|escape:html}" />
<input type="hidden" id="toolTip_addBookmark" value="{$ADD_BOOKMARK}" />
<input type="hidden" id="toolTip_removeBookmark" value="{$REMOVE_BOOKMARK}" />
<input type="hidden" id="toolTip_addingBookmark" value="{$ADDING_BOOKMARK}" />
<input type="hidden" id="toolTip_removingBookmark" value="{$REMOVING_BOOKMARK}" />

<!-- ============================ SIDEBAR ============================ -->
<aside class="iss-sidebar" id="issSidebar">
    <div class="iss-sidebar__brand">
        <a href="index.php" class="iss-logo">
            <img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo.png" alt="Issabel" class="iss-logo__img">
        </a>
        <span class="iss-version">{$issabel_version|default:'6.0'}</span>
    </div>

    <div class="iss-search">
        <i class="ti ti-search"></i>
        <input type="text" id="menuSearch" placeholder="{$MODULES_SEARCH}" data-no-results="{$NO_MODULES_FOUND|escape:'html'}">
    </div>

    <nav class="iss-nav">
        <a href="index.php?menu=dashboard" class="iss-nav__quick{if $idSubMenuSelected eq 'dashboard' or $idSubMenu2Selected eq 'dashboard'} active{/if}">
            <i class="ti ti-layout-dashboard"></i><span>{$MENU_DASHBOARD}</span>
        </a>

        {foreach from=$arrMainMenu key=idMenu item=menu name=menuMain}
        <div class="iss-nav__group">
            <button type="button" class="iss-nav__toggle" data-target="#m-{$idMenu}" aria-expanded="{if $idMenu eq $idMainMenuSelected}true{else}false{/if}">
                <i class="{$menu.icon}"></i><span>{$menu.Name}</span><i class="ti ti-chevron-down iss-chev"></i>
            </button>
            <div class="iss-nav__sub{if $idMenu eq $idMainMenuSelected} expanded{/if}" id="m-{$idMenu}">
                <div class="iss-nav__subinner">
                {foreach from=$menu.children key=idSubMenu item=subMenu}
                    {if $subMenu.children}
                        <button type="button" class="iss-nav__subtoggle" data-target="#s-{$idSubMenu}" aria-expanded="{if $idSubMenu eq $idSubMenuSelected}true{else}false{/if}">
                            <span>{$subMenu.Name}</span><i class="ti ti-chevron-down iss-chev"></i>
                        </button>
                        <div class="iss-nav__third{if $idSubMenu eq $idSubMenuSelected} expanded{/if}" id="s-{$idSubMenu}">
                            <div class="iss-nav__thirdinner">
                            {foreach from=$subMenu.children key=idSubMenu2 item=subMenu2}
                                {if $subMenu2.Type eq "popup"}
                                    <a href="{$subMenu2.Link}" target="{$idSubMenu2}"{if $idSubMenu2 eq $idSubMenu2Selected} class="active"{/if}>{$subMenu2.Name}</a>
                                {else}
                                    <a href="index.php?menu={$idSubMenu2}"{if $idSubMenu2 eq $idSubMenu2Selected} class="active"{/if}>{$subMenu2.Name}</a>
                                {/if}
                            {/foreach}
                            </div>
                        </div>
                    {elseif $subMenu.Type eq "popup"}
                        <a href="{$subMenu.Link}" target="{$idSubMenu}"{if $idSubMenu eq $idSubMenuSelected} class="is-open"{/if}><span>{$subMenu.Name}</span></a>
                    {else}
                        <a href="index.php?menu={$idSubMenu}"{if $idSubMenu eq $idSubMenuSelected} class="is-open"{/if}><span>{$subMenu.Name}</span></a>
                    {/if}
                {/foreach}
                </div>
            </div>
        </div>
        {/foreach}

        <ul class="iss-nav__shortcut">{$SHORTCUT}</ul>
    </nav>

    <div class="iss-sidebar__foot"><i class="ti ti-info-circle"></i> Issabel 6 &middot; GPLv2+</div>
</aside>

<!-- ============================ MAIN ============================ -->
<div class="iss-main">

    <header class="iss-topbar">
        <button class="iss-topbar__burger" id="issBurger" type="button" aria-label="Menu"><i class="ti ti-menu-2"></i></button>
        <div id="iss-tabbar" class="iss-tabbar" hidden></div>

        <div class="iss-topbar__right">
            <!-- Notificaciones -->
            <div class="dropdown">
                <button class="iss-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{$NOTIFICATIONS.LBL_NOTIFICATION_SYSTEM}">
                    <i class="ti ti-bell"></i>{if $NOTIF_COUNT > 0}<span class="iss-badge">{$NOTIF_COUNT}</span>{/if}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header">{$NOTIFICATIONS.LBL_NOTIFICATION_SYSTEM}</li>
                    {foreach from=$NOTIFICATIONS.NOTIFICATIONS_PUBLIC item=NOTI}
                    <li class="{if $NOTI.level eq "info"}iss-noti-info{elseif $NOTI.level eq "warning"}iss-noti-warning{else}iss-noti-danger{/if}">
                        <a class="dropdown-item" href="#" onclick='readNoti("{$NOTI.id}")'>{$NOTI.content}</a>
                    </li>
                    {foreachelse}
                    <li><span class="dropdown-item-text text-secondary">{$NOTIFICATIONS.TXT_NO_NOTIFICATIONS}</span></li>
                    {/foreach}
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header">{$NOTIFICATIONS.LBL_NOTIFICATION_USER}</li>
                    {foreach from=$NOTIFICATIONS.NOTIFICATIONS_PRIVATE item=NOTI}
                    <li class="{if $NOTI.level eq "info"}iss-noti-info{elseif $NOTI.level eq "warning"}iss-noti-warning{else}iss-noti-danger{/if}">
                        <a class="dropdown-item" href="#">{$NOTI.content}</a>
                    </li>
                    {foreachelse}
                    <li><span class="dropdown-item-text text-secondary">{$NOTIFICATIONS.TXT_NO_NOTIFICATIONS}</span></li>
                    {/foreach}
                </ul>
            </div>

            <!-- Usuario -->
            <div class="dropdown iss-user">
                <a href="#" class="iss-user__link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="iss-avatar">{$USER_LOGIN|truncate:2:""|upper}</span>
                    <span class="iss-user__name"><strong>{$USER_LOGIN}</strong>{if !empty($EXTENSION)}<small>#{$EXTENSION}</small>{/if}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item setadminpassword" href="#"><i class="ti ti-user me-2"></i>{$CHANGE_PASSWORD}</a></li>
                    <li><a class="dropdown-item register_link" href="#"><i class="ti ti-contract me-2"></i><span class="iss-register-link__label">{$Registered}</span></a></li>
                    <li><a class="dropdown-item" href="#" id="viewDetailsRPMs"><i class="ti ti-info-circle me-2"></i>{$ABOUT_ISSABEL2}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-header"><i class="ti ti-adjustments-alt me-1"></i>{$APPEARANCE}</li>
                    <li class="px-3 pb-2">
                        <div class="btn-group w-100 iss-theme-toggle" role="group" aria-label="Appearance">
                            <button type="button" class="btn btn-sm" data-theme-set="auto"><i class="ti ti-contrast me-1"></i>{$THEME_AUTO}</button>
                            <button type="button" class="btn btn-sm" data-theme-set="light"><i class="ti ti-sun me-1"></i>{$THEME_LIGHT}</button>
                            <button type="button" class="btn btn-sm" data-theme-set="dark"><i class="ti ti-moon me-1"></i>{$THEME_DARK}</button>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="index.php?logout=yes"><i class="ti ti-logout me-2"></i>{$LOGOUT}</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="iss-pagehead">
        <div class="iss-pagehead__text">
            <nav class="iss-breadcrumb" id="iss-active-breadcrumb" aria-label="Breadcrumb">
                <a href="index.php"><i class="ti ti-home"></i></a>
                {foreach from=$BREADCRUMB item=value name=menu}
                    <i class="ti ti-chevron-right"></i>
                    {if $smarty.foreach.menu.last}
                        <span>{$value}</span>
                    {else}
                        <a href="#">{$value}</a>
                    {/if}
                {/foreach}
            </nav>
            <h1 id="iss-active-page-title" class="iss-pagehead__title">{$PAGE_TITLE|default:''}</h1>
        </div>
    </div>

    <div class="iss-content iss-tabs-enabled">
    {* ABIERTO: lo cierra _common/index.tpl *}
