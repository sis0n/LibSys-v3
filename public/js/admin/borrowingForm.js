document.addEventListener('DOMContentLoaded', () => {

  // --- SweetAlert Helper Functions (Galing sa Book Catalog Design) ---
  // 1. SUCCESS/ADD/UPDATE Toast (Green Theme)
  function showSuccessToast(title, body = "") {
    if (typeof Swal == "undefined") return alert(title);
    Swal.fire({
      toast: true,
      position: "bottom-end",
      showConfirmButton: false,
      timer: 3000,
      width: "360px",
      background: "transparent",
      html: `
        <div class="flex flex-col text-left">
          <div class="flex items-center gap-3 mb-2">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600">
              <i class="ph ph-check-circle text-lg"></i>
            </div>
            <div>
              <h3 class="text-[15px] font-semibold text-green-600">${title}</h3>
              <p class="text-[13px] text-gray-700 mt-0.5">${body}</p>
            </div>
          </div>
        </div>
      `,
      customClass: {
        popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
      },
    });
  }

  // 2. ERROR/VALIDATION Toast (Red Theme)
  function showErrorToast(title, body = "An error occurred during processing.") {
    if (typeof Swal == "undefined") return alert(title);
    Swal.fire({
      toast: true,
      position: "bottom-end",
      showConfirmButton: false,
      timer: 4000,
      width: "360px",
      background: "transparent",
      html: `
        <div class="flex flex-col text-left">
          <div class="flex items-center gap-3 mb-2">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
              <i class="ph ph-x-circle text-lg"></i>
            </div>
            <div>
              <h3 class="text-[15px] font-semibold text-red-600">${title}</h3>
              <p class="text-[13px] text-gray-700 mt-0.5">${body}</p>
            </div>
          </div>
        </div>
      `,
      customClass: {
        popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
      },
    });
  }

  // 3. LOADING Modal (Orange Theme) - bigger version for SUBMITTING
  function showLoadingModal(message = "Processing request...", subMessage = "Please wait.") {
    if (typeof Swal == "undefined") return;
    Swal.fire({
      background: "transparent",
      html: `
        <div class="flex flex-col items-center justify-center gap-3">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-200 border-t-orange-600"></div>
          <p class="text-gray-700 text-[15px] font-semibold">${message}</p>
          <span class="text-[13px] text-gray-500">${subMessage}</span>
        </div>
      `,
      allowOutsideClick: false,
      showConfirmButton: false,
      customClass: {
        popup: "!w-64 !rounded-xl !shadow-md !border-2 !border-orange-400 !p-7 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_9px_#ffb34770]",
      },
    });
  }

  // 4. CONFIRMATION Modal (Orange Theme)
  async function showConfirmationModal(title, text, confirmText = "Confirm") {
    if (typeof Swal == "undefined") return confirm(title);
    const result = await Swal.fire({
      background: "transparent",
      html: `
        <div class="flex flex-col text-center">
          <div class="flex justify-center mb-3">
            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
              <i class="ph ph-warning-circle text-2xl"></i>
            </div>
          </div>
          <h3 class="text-[17px] font-semibold text-orange-700">${title}</h3>
          <p class="text-[14px] text-gray-700 mt-1">${text}</p>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: "Cancel",
      customClass: {
        popup: "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400 shadow-[0_0_8px_#ffb34770]",
        confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700",
        cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300",
      },
    });
    return result.isConfirmed;
  }
  // --- End SweetAlert Helper Functions ---

  const setupDropdown = (btnId, menuId, valueId, inputId, itemClass, callback) => {
    const dropdownBtn = document.getElementById(btnId);
    const dropdownMenu = document.getElementById(menuId);
    const dropdownValue = document.getElementById(valueId);
    const hiddenInput = document.getElementById(inputId);
    if (!dropdownBtn || !dropdownMenu || !dropdownValue || !hiddenInput) return;

    dropdownBtn.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelectorAll('.absolute.z-10, .absolute.z-20').forEach(menu => {
        if (menu.id !== menuId) menu.classList.add('hidden');
      });
      dropdownMenu.classList.toggle('hidden');
    });

    dropdownMenu.addEventListener('click', (e) => {
      const target = e.target.closest(`.${itemClass}`);
      if (target) {
        const val = target.dataset.value;
        dropdownValue.textContent = val;
        hiddenInput.value = val;
        dropdownMenu.classList.add('hidden');
        if (callback) callback(val);
      }
    });
  };

  const itemIcon = document.getElementById('item_icon');
  const itemIdWrapper = document.getElementById('item_id_wrapper');
  const itemNameWrapper = document.getElementById('item_name_wrapper');
  const accessionWrapper = document.getElementById('accession_number_wrapper');
  const bookTitleWrapper = document.getElementById('book_title_wrapper');

  const handleItemTypeChange = (type) => {
    if (!itemIcon) return;

    if (type === 'Book') {
      itemIcon.className = 'ph ph-book-open text-3xl text-emerald-600';
      if (itemIdWrapper) itemIdWrapper.style.display = 'none';
      if (itemNameWrapper) itemNameWrapper.style.display = 'none';
      if (accessionWrapper) accessionWrapper.style.display = 'block';
      if (bookTitleWrapper) bookTitleWrapper.style.display = 'block';
    } else {
      itemIcon.className = 'ph ph-desktop text-3xl text-emerald-600';
      if (itemIdWrapper) itemIdWrapper.style.display = 'block';
      if (itemNameWrapper) itemNameWrapper.style.display = 'block';
      if (accessionWrapper) accessionWrapper.style.display = 'none';
      if (bookTitleWrapper) bookTitleWrapper.style.display = 'none';
      document.getElementById('accession_number').value = '';
      document.getElementById('book_title').value = '';
    }
  };

  setupDropdown('itemTypeDropdownBtn', 'itemTypeDropdownMenu', 'itemTypeDropdownValue', 'item_type', 'item-type-item', handleItemTypeChange);
  setupDropdown('roleDropdownBtn', 'roleDropdownMenu', 'roleDropdownValue', 'role', 'role-item');

  document.getElementById('clear-btn').addEventListener('click', () => {
    const form = document.getElementById('main-borrow-form');
    form.reset();
    document.getElementById('roleDropdownValue').textContent = 'Select Role';
    document.getElementById('itemTypeDropdownValue').textContent = 'Equipment';
    document.getElementById('role').value = '';
    document.getElementById('item_type').value = 'Equipment';
    handleItemTypeChange('Equipment');
    showSuccessToast('Form Cleared', 'Borrower and Item fields have been reset.');
  });

  // check user button
  document.getElementById('check-btn').addEventListener('click', async () => {
    const userId = document.getElementById('input_user_id').value.trim();

    if (!userId) return showErrorToast('Input Required', 'Please enter a **User ID**.');

    showLoadingModal('Checking User...', 'Verifying User ID in the database.');

    try {
      const res = await fetch(`api/admin/borrowingForm/checkUser`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ input_user_id: userId })
      });
      const data = await res.json();

      await new Promise(r => setTimeout(r, 300));
      Swal.close();

      if (data.exists) {
        const fill = await showConfirmationModal(
          'User ID Exists',
          'This User ID already exists. Do you want to **fill the remaining input fields** with existing data?',
          'Yes, Fill Fields'
        );

        if (fill) {
          document.querySelector('input[name="first_name"]').value = data.data.first_name;
          document.querySelector('input[name="middle_name"]').value = data.data.middle_name || '';
          document.querySelector('input[name="last_name"]').value = data.data.last_name;
          document.querySelector('input[name="suffix"]').value = data.data.suffix || '';
          document.querySelector('input[name="email"]').value = data.data.email || '';
          document.querySelector('input[name="contact"]').value = data.data.contact || '';

          const roleDropdown = document.getElementById('roleDropdownValue');
          const roleHiddenInput = document.getElementById('role');
          if (roleDropdown && roleHiddenInput) {
            const displayRole = data.data.role.charAt(0).toUpperCase() + data.data.role.slice(1);
            roleDropdown.textContent = displayRole;
            roleHiddenInput.value = displayRole;
          }
          showSuccessToast('Fields Auto-Filled', 'User data loaded successfully.');
        }
      } else {
        showErrorToast('User Not Found', 'User ID not found. Please fill the form manually (Guest Mode).');
      }
    } catch (err) {
      Swal.close();
      console.error(err);
      showErrorToast('Connection Failed', 'An unexpected error occurred while checking the user.');
    }
  });

  // submit (TADO!)
  document.getElementById('main-borrow-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    showLoadingModal('Submitting Form...', 'Processing borrowing transaction.');

    if (!formData.get('role')) formData.set('role', '');
    if (!formData.get('equipment_type')) formData.set('equipment_type', formData.get('item_type'));

    try {
      const res = await fetch(`api/admin/borrowingForm/create`, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      await new Promise(r => setTimeout(r, 300));
      Swal.close();

      if (data.success) {
        showSuccessToast('Transaction Successful!', data.message || 'Item successfully borrowed.');
        form.reset();
      } else {
        showErrorToast('Submission Failed', data.message || 'Please check all required fields.');
      }

    } catch (err) {
      Swal.close();
      console.error(err);
      showErrorToast('Connection Failed', 'An error occurred while submitting the form.');
    }
  });

  document.addEventListener('click', (e) => {
    document.querySelectorAll('.absolute.z-10, .absolute.z-20').forEach(menu => {
      const btn = document.getElementById(menu.id.replace('Menu', 'Btn'));
      if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.add('hidden');
      }
    });
  });

  handleItemTypeChange('Equipment');

  const setupCombobox = (inputId, suggestionsId, listId, arrowId, fetchUrl, fallbackData = [], highlightClass, hoverClass) => {
    const input = document.getElementById(inputId);
    const suggestionsContainer = document.getElementById(suggestionsId);
    const suggestionsList = document.getElementById(listId);
    const dropdownArrow = document.getElementById(arrowId);

    if (!input || !suggestionsContainer || !suggestionsList || !dropdownArrow) return;

    let suggestions = [];
    let highlightedIndex = -1;
    let wasPointerDownOnInput = false;

    const fetchSuggestionsData = async () => {
      try {
        const response = await fetch(fetchUrl);
        suggestions = response.ok ? await response.json() : fallbackData;
      } catch {
        suggestions = fallbackData;
      }
    };

    if (fetchUrl) fetchSuggestionsData();
    else suggestions = fallbackData;

    const updateHighlight = () => {
      const items = suggestionsList.querySelectorAll('li');
      items.forEach(item => item.classList.remove(highlightClass));
      if (items[highlightedIndex]) items[highlightedIndex].classList.add(highlightClass);
    };

    const showSuggestions = (filter = true) => {
      const value = input.value.toLowerCase();
      suggestionsList.innerHTML = '';
      highlightedIndex = -1;
      let filtered = suggestions;

      if (filter && value.trim() !== '') filtered = suggestions.filter(s => s.toLowerCase().includes(value));
      if (filtered.length === 0 && value.trim() !== '') return suggestionsContainer.classList.add('hidden');

      filtered.forEach(suggestion => {
        const li = document.createElement('li');
        li.className = `px-4 py-2 text-sm cursor-pointer ${hoverClass}`;
        li.textContent = suggestion;
        li.addEventListener('mousedown', e => {
          e.preventDefault();
          input.value = suggestion;
          hideSuggestions();
        });
        suggestionsList.appendChild(li);
      });

      suggestionsContainer.classList.remove('hidden');
    };

    const hideSuggestions = () => {
      suggestionsContainer.classList.add('hidden');
      highlightedIndex = -1;
      wasPointerDownOnInput = false;
    };

    input.addEventListener('pointerdown', () => wasPointerDownOnInput = true);
    input.addEventListener('focus', () => { if (wasPointerDownOnInput) showSuggestions(false); });
    input.addEventListener('input', () => showSuggestions(true));
    dropdownArrow.addEventListener('pointerdown', () => wasPointerDownOnInput = true);
    dropdownArrow.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); suggestionsContainer.classList.contains('hidden') ? showSuggestions(false) && input.focus() : hideSuggestions(); });

    input.addEventListener('keydown', e => {
      const items = suggestionsList.querySelectorAll('li');
      if (items.length === 0) return;
      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          highlightedIndex = (highlightedIndex + 1) % items.length;
          updateHighlight();
          break;
        case 'ArrowUp':
          e.preventDefault();
          highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
          updateHighlight();
          break;
        case 'Enter':
          e.preventDefault();
          if (highlightedIndex > -1) {
            input.value = items[highlightedIndex].textContent;
            hideSuggestions();
          }
          break;
        case 'Escape':
          hideSuggestions();
          break;
      }
    });

    document.addEventListener('click', e => {
      if (!input.contains(e.target) && !dropdownArrow.contains(e.target) && !suggestionsContainer.contains(e.target)) hideSuggestions();
    });
  };

  setupCombobox(
    'item_name',
    'item_name_suggestions',
    'item_name_suggestions_list',
    'item_name_dropdown_arrow',
    'api/admin/borrowingForm/getEquipments',
    ['Computer', 'Table', 'Extension Cord', 'Whiteboard', 'HDMI Cable', 'Chess', 'Scrabble', 'Domino', 'Connect 4'],
    'bg-emerald-100',
    'hover:bg-emerald-50'
  );

  setupCombobox(
    'collateral_id',
    'collateral_id_suggestions',
    'collateral_id_suggestions_list',
    'collateral_id_dropdown_arrow',
    'api/admin/borrowingForm/getCollaterals',
    ['School ID', 'Library ID','Valid ID'],
    'bg-amber-100',
    'hover:bg-amber-50'
  );

});
