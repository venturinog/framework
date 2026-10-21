<form class="issabel-standard-formgrid iss-grid" id="idformgrid" method="POST" style="margin-bottom:0;" action="{$url}">
{* Botón invisible al inicio del form que impide que el primer botón visible del filtro, frecuentemente Borrar, sea default *}
<input type="submit" name="" value="" style="height: 0; min-height: 0; font-size: 0; width: 0; border: none; outline: none; padding: 0px; margin: 0px; box-sizing: border-box; float: left;" />

    <div class="iss-grid__toolbar">
        {foreach from=$arrActions key=k item=accion name=actions}
            {if $accion.type eq 'link'}
                <a href="{$accion.task}" class="iss-grid__action" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if}>
                    <button type="button" name="{$accion.task}" value="{$accion.alt}" class="btn btn-sm btn-outline-primary" {if !empty($accion.ocolor)} style="background-color:#{$accion.ocolor}; border:1px solid #{$accion.ocolor}; color:#fff;" {/if}>
                       {if !empty($accion.iconclass)}<i class="{$accion.iconclass}"></i> {elseif !empty($accion.icon)}<img border="0" src="{$accion.icon}" align="absmiddle" />{/if}{$accion.alt}
                    </button>
                </a>
            {elseif $accion.type eq 'button'}
                <button type="button" name="{$accion.task}" value="{$accion.alt}" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="btn btn-sm btn-outline-primary" {if !empty($accion.ocolor)} style="background-color:#{$accion.ocolor}; border:1px solid #{$accion.ocolor}; color:#fff;" {/if}>
                   {if !empty($accion.iconclass)}<i class="{$accion.iconclass}"></i> {elseif !empty($accion.icon)}<img border="0" src="{$accion.icon}" align="absmiddle" />{/if}{$accion.alt}
                </button>
            {elseif $accion.type eq 'submit'}
                <button type="submit" name="{$accion.task}" value="{$accion.alt}" {if !empty($accion.onclick)} onclick="{$accion.onclick}" {/if} class="btn btn-sm btn-outline-primary" {if !empty($accion.ocolor)} style="background-color:#{$accion.ocolor}; border:1px solid #{$accion.ocolor}; color:#fff;" {/if}>
                   {if !empty($accion.iconclass)}<i class="{$accion.iconclass}"></i> {elseif !empty($accion.icon)}<img border="0" src="{$accion.icon}" align="absmiddle" />{/if}{$accion.alt}
                </button>
            {elseif $accion.type eq 'text'}
                <div class="input-group input-group-sm iss-grid__text">
                    <input type="text" id="{$accion.name}" name="{$accion.name}" value="{$accion.value}" class="form-control" {if !empty($accion.onkeypress)} onkeypress="{$accion.onkeypress}" {/if} />
                    <button type="submit" name="{$accion.task}" value="{$accion.alt}" class="btn btn-outline-primary"><i class="ti ti-check"></i> {$GRID_APPLY}</button>
                </div>
            {elseif $accion.type eq 'combo'}
                <div class="iss-grid__combo">
                    {* El rótulo se muestra junto al selector y además viaja como value del botón *}
                    {if !empty($accion.alt)}<label class="iss-grid__combo-label" for="{$accion.name}">{$accion.alt}</label>{/if}
                    <select name="{$accion.name}" id="{$accion.name}" class="form-select form-select-sm" {if !empty($accion.onchange)} onchange="{$accion.onchange}" {/if}>
                        {if !empty($accion.selected)}
                            {html_options options=$accion.arrOptions selected=$accion.selected}
                        {else}
                            {html_options options=$accion.arrOptions}
                        {/if}
                    </select>
                    {if !empty($accion.task)}
                        <button type="submit" name="{$accion.task}" value="{$accion.alt}" class="btn btn-sm btn-outline-primary"><i class="ti ti-check"></i> {$GRID_APPLY}</button>
                    {/if}
                </div>
            {elseif $accion.type eq 'html'}
                <div class="iss-grid__html">{$accion.html}</div>
            {/if}
        {/foreach}

        {if !empty($contentFilter)}
            <button type="button" class="btn btn-sm btn-outline-primary" id="neo-table-button-filter-left">
                <i class="ti ti-filter"></i> <span id="neo-table-label-filter">{if $AS_OPTION}{$MORE_OPTIONS}{else}{$FILTER_GRID_SHOW}{/if}</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="neo-table-filter-button-arrow">
               <i class="ti ti-chevron-down"></i>
            </button>
        {/if}

        {if $enableExport==true}
            <button type="button" class="btn btn-sm btn-outline-primary" id="export_button">
                <i class="ti ti-download"></i> {$DOWNLOAD_GRID}
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="neo-table-button-download-right">
               <i class="ti ti-chevron-down"></i>
            </button>

            <div id="subMenuExport" class="subMenu neo-display-none" role="menu" aria-haspopup="true">
                <div class="items">
                    <a href="{$url}&exportcsv=yes&rawmode=yes"><div class="menuItem" role="menuitem" id="CSV"><div><i class="ti ti-file-text text-primary"></i>&nbsp;&nbsp;CSV</div></div></a>
                    <a href="{$url}&exportspreadsheet=yes&rawmode=yes"><div class="menuItem" role="menuitem" id="Spread_Sheet"><div><i class="ti ti-file-spreadsheet text-success"></i>&nbsp;&nbsp;Spreadsheet</div></div></a>
                    <a href="{$url}&exportpdf=yes&rawmode=yes"><div class="menuItem" role="menuitem" id="PDF"><div><i class="ti ti-file-type-pdf text-danger"></i>&nbsp;&nbsp;PDF</div></div></a>
                </div>
            </div>
        {/if}

        <div class="iss-grid__nav ms-auto">
            {if $pagingShow and $numPage>1}
                {if $start<=1}
                    <i class="ti ti-chevrons-left iss-nav-disabled"></i><i class="ti ti-chevron-left iss-nav-disabled"></i>
                {else}
                    <a href="{$url}&nav=start&start={$start}" class="ti ti-chevrons-left iss-navigation-arrow-active" title='{$lblStart}'></a>
                    <a href="{$url}&nav=previous&start={$start}" class="ti ti-chevron-left iss-navigation-arrow-active" title='{$lblPrevious}'></a>
                {/if}
                &nbsp;{$lblPage}&nbsp;
                <input type="text" value="{$currentPage}" size="2" align="absmiddle" name="page" id="pageup" class="iss-page-input" />&nbsp;{$lblof}&nbsp;{$numPage}
                <input type="hidden" value="bypage" name="nav" />
                {if $end==$total}
                    <i class="ti ti-chevron-right iss-nav-disabled"></i><i class="ti ti-chevrons-right iss-nav-disabled"></i>
                {else}
                    <a href="{$url}&nav=next&start={$start}" class="ti ti-chevron-right iss-navigation-arrow-active" title='{$lblNext}'></a>
                    <a href="{$url}&nav=end&start={$start}" class="ti ti-chevrons-right iss-navigation-arrow-active" title='{$lblEnd}'></a>
                {/if}
            {/if}
        </div>
    </div>

    {if !empty($contentFilter)}
        <div id="neo-table-header-filterrow" class="iss-grid__filterrow neo-display-none">
            {$contentFilter}
        </div>
    {/if}

    {if !empty($arrFiltersControl)}
        <div class="iss-grid__filtercontrols">
            {foreach from=$arrFiltersControl key=k item=filterc name=filtersctrl}
                <span class="iss-filter-control"><i>{$filterc.msg}</i>&nbsp;
                {if $filterc.defaultFilter eq no}
                    <a href="{$url}&name_delete_filters={$filterc.filters}" class="text-secondary"><i class="ti ti-x"></i></a>
                {/if}
                </span>
            {/foreach}
        </div>
    {/if}

        <table class="issabel-standard-table table table-hover align-middle iss-table" width="100%">
        <thead>
            <tr>
                {section name=columnNum loop=$numColumns start=0 step=1}
                <th>{$header[$smarty.section.columnNum.index].name}&nbsp;</th>
                {/section}
            </tr>
        </thead>
        <tbody>
            {if $numData > 0}
                {foreach from=$arrData key=k item=data name=filas}
                {if $data.ctrl eq 'separator_line'}
                    <tr>
                        {if $data.start > 0}<td colspan="{$data.start}"></td>{/if}
                        {assign var="data_start" value="`$data.start`"}
                        <td colspan="{$numColumns-$data.start}" class="iss-separator"></td>
                    </tr>
                {else}
                    <tr>
                        {if $smarty.foreach.filas.last}
                            {section name=columnNum loop=$numColumns start=0 step=1}
                                <td class="table_data_last_row">{if $data[$smarty.section.columnNum.index] eq ''}&nbsp;{/if}{$data[$smarty.section.columnNum.index]}</td>
                            {/section}
                        {else}
                            {section name=columnNum loop=$numColumns start=0 step=1}
                                <td class="table_data">{if $data[$smarty.section.columnNum.index] eq ''}&nbsp;{/if}{$data[$smarty.section.columnNum.index]}</td>
                            {/section}
                        {/if}
                    </tr>
                {/if}
                {/foreach}
            {else}
                <tr>
                    <td class="table_data iss-empty" colspan="{$numColumns}">
                        <i class="ti ti-inbox"></i>
                        <div>{$NO_DATA_FOUND}</div>
                    </td>
                </tr>
            {/if}
        </tbody>
        </table>

    {if $numData > 3 and $numPage > 1}
        <div class="iss-grid__footer">
            <div class="iss-grid__nav">
            {if $pagingShow and $numPage>1}
                {if $start<=1}
                    <i class="ti ti-chevrons-left iss-nav-disabled"></i><i class="ti ti-chevron-left iss-nav-disabled"></i>
                {else}
                    <a href="{$url}&nav=start&start={$start}" class="ti ti-chevrons-left iss-navigation-arrow-active" title='{$lblStart}'></a>
                    <a href="{$url}&nav=previous&start={$start}" class="ti ti-chevron-left iss-navigation-arrow-active" title='{$lblPrevious}'></a>
                {/if}
                &nbsp;{$lblPage}&nbsp;
                <input type="text" value="{$currentPage}" size="2" align="absmiddle" name="page" id="pagedown" class="iss-page-input" />&nbsp;{$lblof}&nbsp;{$numPage}
                <input type="hidden" value="bypage" name="nav" />
                {if $end==$total}
                    <i class="ti ti-chevron-right iss-nav-disabled"></i><i class="ti ti-chevrons-right iss-nav-disabled"></i>
                {else}
                    <a href="{$url}&nav=next&start={$start}" class="ti ti-chevron-right iss-navigation-arrow-active" title='{$lblNext}'></a>
                    <a href="{$url}&nav=end&start={$start}" class="ti ti-chevrons-right iss-navigation-arrow-active" title='{$lblEnd}'></a>
                {/if}
            {/if}
            </div>
        </div>
    {/if}
</form>

<div class="modal" id="gridModal" tabindex="-1" role="dialog">
  <div class="modal-dialog {$modalClass}" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{#modalTitle#}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="gridModalContent">
        {$modalContent}
      </div>
    </div>
  </div>
</div>

{literal}
<script type="text/javascript">
$(document).ready(function () {
    $("[id^=page]").keyup(function (event) {
        var id = $(this).attr("id");
        var val = $(this).val();
        if (id == "pageup") $("#pagedown").val(val);
        else if (id == "pagedown") $("#pageup").val(val);
    });

    /* Toggle del row de filtros. Con click delegado en document, para que el
     * registro no dependa del orden de carga de los scripts ni de que el
     * contenido esté en el DOM al momento de la asignación. Cubre ambos
     * controles: el botón con el texto ("Show Filter" / "Hide Filter") y el
     * chevron, que son visualmente dos botones distintos desde el restyle
     * del tema modern. */
    $(document).on("click", "#neo-table-button-filter-left, #neo-table-filter-button-arrow", function (e) {
        e.preventDefault();
        e.stopPropagation();
{/literal}
    {if $AS_OPTION}
        var filter_show = "{$MORE_OPTIONS}";
        var filter_hide = "{$MORE_OPTIONS}";
    {else}
        var filter_show = "{$FILTER_GRID_SHOW}";
        var filter_hide = "{$FILTER_GRID_HIDE}";
    {/if}
{literal}
        var $row = $("#neo-table-header-filterrow");
        if ($row.hasClass("neo-display-none")) {
            $row.removeClass("neo-display-none");
            $("#neo-table-label-filter").text(filter_hide);
            $("#neo-table-filter-button-arrow i").removeClass("ti-chevron-down").addClass("ti-chevron-up");
        } else {
            $row.addClass("neo-display-none");
            $("#neo-table-label-filter").text(filter_show);
            $("#neo-table-filter-button-arrow i").removeClass("ti-chevron-up").addClass("ti-chevron-down");
        }
    });
});
{/literal}

{$customJS}
</script>
