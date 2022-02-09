import "../css/admin.scss";

declare global {
    interface Window {
        $: JQueryStatic;
    }
}

window.$ = jQuery;

$(() => {
    $(".framering-repeater").each(function() {
        const $repeater = $<HTMLDivElement>(this as HTMLDivElement);
        const $items = $repeater.find(".items:first");
        const $actions = $repeater.find(".actions:first");

        // Retrieve the repeater name
        const name = $repeater.attr("data-name");

        // Find the repeater template contents
        const template = $(`script[type="text/framering-repeater"][data-for="${name}"]`).html();
        
        // When clicking to add a new repeater item
        $actions.find(`button[data-role="repeater-add-new"]`).on("click", (e) => {
            e.preventDefault();

            const $item = $(`
                <div class="repeater-item">
                    <button class="button delete button-delete-item is-destructive">X</button>
                    ${template}
                </div>
            `);

            // Add the new item array index to it
            $item.find(":input").each(function() {
                $(this).attr("name", $(this).attr("name"));
            });

            $items.append($item);
        });
    });
});