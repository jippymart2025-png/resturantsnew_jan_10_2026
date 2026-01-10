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
$(document).ready(function() {

    // 1. Filter dropdown options based on search
    $('#food_category_search').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        $('#food_category option').each(function() {
            if ($(this).val() === "") return; // skip placeholder
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(search) > -1);
        });
    });

    // 2. When selecting from dropdown, update tags
    $('#food_category').on('change', function() {
        updateSelectedFoodCategoryTags();
    });

    // 3. Remove tag and unselect dropdown
    $('#selected_categories').on('click', '.remove-tag', function() {
        var value = $(this).closest('.selected-category-tag').data('value');
        $('#food_category option[value="' + value + '"]').prop('selected', false);
        updateSelectedFoodCategoryTags();
    });

    // 4. Show tags on page load (important for edit form)
    updateSelectedFoodCategoryTags();
});

    // 5. Tag generation
function updateSelectedFoodCategoryTags() {
    var html = '';
    $('#food_category option:selected').each(function() {
        if ($(this).val() !== "") {
            html += `
                <span class="selected-category-tag" data-value="${$(this).val()}">
                    ${$(this).text()}
                    <span class="remove-tag">&times;</span>
                </span>`;
        }
    });
    $('#selected_categories').html(html);
}

// Availability Scheduling JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-availability');
    const availabilitySection = document.getElementById('availability-section');
    const availabilitySummary = document.getElementById('availability-summary');
    const dayCheckboxes = document.querySelectorAll('.day-checkbox');
    
    // Toggle availability section
    if (toggleBtn && availabilitySection) {
        toggleBtn.addEventListener('click', function() {
            const isVisible = availabilitySection.style.display !== 'none';
            availabilitySection.style.display = isVisible ? 'none' : 'block';
            if (availabilitySummary) {
                availabilitySummary.style.display = isVisible ? 'block' : 'none';
            }
            toggleBtn.innerHTML = isVisible 
                ? '<i class="fa fa-clock mr-1"></i> Manage Schedule'
                : '<i class="fa fa-eye mr-1"></i> Hide Schedule';
        });
    }
    
    // Handle day checkbox changes
    dayCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const day = this.dataset.day;
            const dayTimingsGroup = document.querySelector(`.day-timings-group[data-day="${day}"]`);
            
            if (this.checked) {
                if (dayTimingsGroup) {
                    dayTimingsGroup.style.display = 'block';
                    // If no time slots exist, add one empty slot
                    const timingsList = dayTimingsGroup.querySelector('.timings-list');
                    if (timingsList && timingsList.children.length === 0) {
                        addTimeSlot(day);
                    }
                }
            } else {
                if (dayTimingsGroup) {
                    dayTimingsGroup.style.display = 'none';
                }
            }
        });
    });
    
    // Add time slot
    document.querySelectorAll('.add-time-slot').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const day = this.dataset.day;
            addTimeSlot(day);
        });
    });
    
    // Remove time slot
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-time-slot')) {
            const btn = e.target.closest('.remove-time-slot');
            const timingRow = btn.closest('.timing-row');
            if (timingRow) {
                const timingsList = timingRow.parentElement;
                timingRow.remove();
                // If no time slots remain for this day, but day is checked, add one empty slot
                if (timingsList.children.length === 0) {
                    const day = timingRow.dataset.day;
                    const dayCheckbox = document.getElementById(`day_${day}`);
                    if (dayCheckbox && dayCheckbox.checked) {
                        addTimeSlot(day);
                    }
                }
            }
        }
    });
    
    // Helper function to add time slot
    function addTimeSlot(day) {
        const dayTimingsGroup = document.querySelector(`.day-timings-group[data-day="${day}"]`);
        if (!dayTimingsGroup) return;
        
        const timingsList = dayTimingsGroup.querySelector('.timings-list');
        if (!timingsList) return;
        
        // Get the highest index for this day
        const existingRows = timingsList.querySelectorAll('.timing-row');
        let maxIndex = -1;
        existingRows.forEach(row => {
            const index = parseInt(row.dataset.index || -1);
            if (index > maxIndex) maxIndex = index;
        });
        const newIndex = maxIndex + 1;
        
        const row = document.createElement('div');
        row.className = 'row align-items-end mb-2 timing-row';
        row.dataset.day = day;
        row.dataset.index = newIndex;
        row.innerHTML = `
            <div class="col-md-4">
                <label class="form-label small">From</label>
                <input type="time" 
                       name="available_timings[${day}][${newIndex}][from]" 
                       class="form-control form-control-sm time-slot-from">
            </div>
            <div class="col-md-4">
                <label class="form-label small">To</label>
                <input type="time" 
                       name="available_timings[${day}][${newIndex}][to]" 
                       class="form-control form-control-sm time-slot-to">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-time-slot w-100" title="Remove time slot">
                    <i class="fa fa-times mr-1"></i> Remove
                </button>
            </div>
        `;
        timingsList.appendChild(row);
        
        // Focus on the new from input
        const fromInput = row.querySelector('.time-slot-from');
        if (fromInput) {
            fromInput.focus();
        }
    }
});
</script>

