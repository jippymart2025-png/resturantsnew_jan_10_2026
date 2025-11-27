<script>
document.addEventListener('DOMContentLoaded', function () {
    const addOnTemplate = document.getElementById('add-on-template');
    const specTemplate = document.getElementById('spec-template');

    function appendClone(template, containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container || !template) {
            return;
        }

        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }

    document.querySelectorAll('[data-addons-add]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            appendClone(addOnTemplate, '[data-addons-container]');
        });
    });

    document.querySelectorAll('[data-specs-add]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            appendClone(specTemplate, '[data-specs-container]');
        });
    });

    document.addEventListener('click', function (event) {
        if (event.target.matches('[data-remove-row]')) {
            event.preventDefault();
            const row = event.target.closest('.repeatable-row');
            if (row) {
                row.remove();
            }
        }
    });
});
</script>

