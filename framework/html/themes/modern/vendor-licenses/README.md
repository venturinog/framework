# Librerías vendorizadas en el tema `modern` — licencias

Estos archivos se distribuyen **tal cual** (build `dist`/`min` ya compilado) y no
se modifican. Ver `GUItodo.md` §5 y `STYLE.md` §10.

| Archivo en `js/` | Librería | Versión | Licencia | Origen |
|---|---|---|---|---|
| `chart.umd.min.js` | Chart.js | 4.5.1 | MIT | https://www.chartjs.org |
| `chartjs-plugin-datalabels.min.js` | chartjs-plugin-datalabels | 2.2.0 | MIT | https://chartjs-plugin-datalabels.netlify.app |
| `moment.min.js` | Moment.js | 2.30.1 | MIT | https://momentjs.com |
| `chartjs-adapter-moment.min.js` | chartjs-adapter-moment | 1.0.0 | MIT | https://github.com/chartjs/chartjs-adapter-moment |

`issabel-charts.js` es código propio de Issabel (GPLv2+), no de terceros.

Se usa el adaptador **moment** porque los módulos de `reports` ya dependen de
moment para el formato de fechas (`tooltipFormat: 'lll'`), y así la migración no
cambia formatos. Es la dependencia de menor riesgo; si más adelante se quiere
retirar moment, se puede pasar a `chartjs-adapter-date-fns`.

No usar en ningún caso una versión de Chart.js `5+`: su licencia ya no es MIT.

Los textos completos de licencia están en este mismo directorio.
