window.IRBDropdown = window.IRBDropdown || (function () {
    function closeAll(exceptRoot) {
        document.querySelectorAll('.irb-dropdown.is-open').forEach((dropdown) => {
            if (dropdown !== exceptRoot) {
                dropdown.classList.remove('is-open');
            }
        });
    }

    function syncDropdown(dropdown) {
        const select = dropdown.querySelector('select');
        const label = dropdown.querySelector('.irb-dropdown__label');
        const selectedOption = select.options[select.selectedIndex];

        label.textContent = selectedOption && selectedOption.value
            ? selectedOption.textContent.trim()
            : select.dataset.placeholder || label.dataset.placeholder || 'اختر';

        dropdown.querySelectorAll('.irb-dropdown__option').forEach((optionButton) => {
            optionButton.classList.toggle('is-selected', optionButton.dataset.value === select.value);
            optionButton.setAttribute('aria-selected', String(optionButton.dataset.value === select.value));
        });
    }

    function buildDropdown(select) {
        if (select.dataset.irbDropdownInitialized === 'true') {
            return;
        }

        select.dataset.irbDropdownInitialized = 'true';

        const dropdown = document.createElement('div');
        dropdown.className = 'irb-dropdown';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'irb-dropdown__button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');

        const label = document.createElement('span');
        label.className = 'irb-dropdown__label';
        label.dataset.placeholder = select.dataset.placeholder || select.querySelector('option')?.textContent?.trim() || 'اختر';

        const icon = document.createElement('span');
        icon.className = 'irb-dropdown__icon';
        icon.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';

        button.appendChild(label);
        button.appendChild(icon);

        const menu = document.createElement('div');
        menu.className = 'irb-dropdown__menu';
        menu.setAttribute('role', 'listbox');

        Array.from(select.options).forEach((option) => {
            const optionButton = document.createElement('button');
            optionButton.type = 'button';
            optionButton.className = 'irb-dropdown__option';
            optionButton.dataset.value = option.value;
            optionButton.setAttribute('role', 'option');
            optionButton.setAttribute('aria-selected', String(option.selected));
            optionButton.textContent = option.textContent.trim();

            optionButton.addEventListener('click', () => {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                dropdown.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
                syncDropdown(dropdown);
            });

            menu.appendChild(optionButton);
        });

        button.addEventListener('click', () => {
            const isOpen = dropdown.classList.contains('is-open');
            closeAll(dropdown);
            dropdown.classList.toggle('is-open', !isOpen);
            button.setAttribute('aria-expanded', String(!isOpen));
        });

        select.addEventListener('change', () => {
            syncDropdown(dropdown);
        });

        dropdown.appendChild(button);
        dropdown.appendChild(menu);
        select.insertAdjacentElement('afterend', dropdown);
        select.classList.add('irb-select--native');

        syncDropdown(dropdown);
    }

    function init(selector) {
        document.querySelectorAll(selector || 'select.irb-select--custom').forEach(buildDropdown);

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.irb-dropdown')) {
                closeAll(null);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAll(null);
            }
        });
    }

    return {
        init,
    };
}());
