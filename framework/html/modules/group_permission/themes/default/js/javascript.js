$(document).ready(function() {
    // Botón de expandir/colapsar de los nodos con hijos. El icono refleja el
    // estado: chevron abajo = expandido, chevron derecha = colapsado.
    $('button.resource-branch-manip').click(function(event) {
        event.preventDefault();
        var tr_button = $(this).parents('tr').first();
        if ($(this).find('i').hasClass('ti-chevron-down')) {
            collapseChildren(tr_button);
        } else {
            expandChildren(tr_button);
        }
    });

    $('input[type=checkbox][name="resource_access[]"]').click(function(event) {
        // Actualizar la cuenta de módulos activos
        var curmodule_id = $(this).parent('td').parent('tr').find('input[name=id]').val();
        var parentmodule_id;
        do {
            parentmodule_id = $('input[name=id][value='+curmodule_id+']').parent('td').parent('tr').find('input[name=idparent]').val();
            if (parentmodule_id != undefined) curmodule_id = parentmodule_id;
        } while (parentmodule_id != undefined);

        updateEnabledCount(curmodule_id);
    });

    // Colapsar todos los niveles
    $('button.level-1').click();
});

function expandChildren(tr_button)
{
    var resource_id = tr_button.find('input[name=id]').val();
    $('input[name=idparent][value='+resource_id+']').parent('td').parent('tr').each(function () {
        $(this).show();
    });
    tr_button.find('button > i')
        .removeClass('ti-chevron-right')
        .addClass('ti-chevron-down');
    tr_button.find('button').attr('aria-expanded', 'true');
}

function collapseChildren(tr_button)
{
    var resource_id = tr_button.find('input[name=id]').val();
    $('input[name=idparent][value='+resource_id+']').parent('td').parent('tr').each(function () {
        collapseChildren($(this));
        $(this).hide();
    });

    tr_button.find('button > i')
        .removeClass('ti-chevron-down')
        .addClass('ti-chevron-right');
    tr_button.find('button').attr('aria-expanded', 'false');
}

function updateEnabledCount(curmodule_id)
{
    // Enumerar hijos, si existen
    var children_list = $('input[name=idparent][value='+curmodule_id+']')
        .parent('td').parent('tr');
    if (children_list.length > 0) {
        var children_count = 0;
        children_list.each(function() {
            children_count += updateEnabledCount($(this).find('input[name=id]').val());
        });
        var spancount = $('input[name=id][value='+curmodule_id+']').parent('td').parent('tr')
            .find('span.enabledcount');
        if (children_count > 0)
            spancount.css('font-weight', 'bold');
        else
            spancount.css('font-weight', 'normal');
        spancount.text(children_count);
        return children_count;
    } else {
        return $('#resource-access-'+curmodule_id).is(':checked') ? 1 : 0;
    }
}
