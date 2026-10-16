-- Issabel 6: long-term aggregates for the sampler.
--
-- The raw `samples` table is pruned to ~26 h by sampler.php, which limits the
-- trend charts to one day. This table keeps cheap hourly and daily aggregates
-- (avg/min/max/count) so the dashboard can offer 7- and 30-day ranges without
-- growing the database. See DASHBOARDtodo.md §12 and §15.

BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS samples_rollup (
    id_line    integer not null,
    resolution integer not null,          -- 3600 (hourly) or 86400 (daily)
    bucket_ts  integer not null,          -- floor(timestamp / resolution) * resolution
    avg        real,
    vmin       real,
    vmax       real,
    cnt        integer not null default 0,
    PRIMARY KEY (id_line, resolution, bucket_ts)
);

COMMIT;
