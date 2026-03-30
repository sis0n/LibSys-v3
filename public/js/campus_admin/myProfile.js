window.addEventListener("DOMContentLoaded", () => {
  // --- DOM Elements ---
  const profileForm = document.getElementById("profileForm");
  const editProfileBtn = document.getElementById("editProfileBtn");
  const saveProfileBtn = document.getElementById("saveProfileBtn");
  const cancelProfileBtn = document.getElementById("cancelProfileBtn");
  const formActions = document.getElementById("formActions");
  const profileLockedInfo = document.getElementById("profileLockedInfo");

  const genderSelect = document.getElementById("gender");
  const genderOtherInput = document.getElementById("genderOther");
  const campusSelect = document.getElementById("campus");

  // Gender Logic: Toggle 'Other' input visibility
  if (genderSelect) {
    genderSelect.addEventListener("change", function () {
      if (this.value === "Other") {
        genderOtherInput.classList.remove("hidden");
        genderOtherInput.disabled = false;
        genderOtherInput.focus();
      } else {
        genderOtherInput.classList.add("hidden");
        genderOtherInput.value = "";
        genderOtherInput.disabled = true;
      }
    });
  }

  const fields = ['lastName', 'firstName', 'middleName', 'suffix', 'email', 'gender', 'genderOther', 'campus'];
  const inputElements = {};
  fields.forEach(id => inputElements[id] = document.getElementById(id));

  const successMessage = document.createElement('div');
  successMessage.className = 'hidden fixed bottom-5 right-5 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
  document.body.appendChild(successMessage);

  const errorMessage = document.createElement('div');
  errorMessage.className = 'hidden fixed bottom-5 right-5 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
  document.body.appendChild(errorMessage);

  let originalFormState = {};

  function showMessage(element, message) {
    element.textContent = message;
    element.classList.remove('hidden');
    setTimeout(() => {
      element.classList.add('hidden');
    }, 3000);
  }

  async function loadCampuses(currentCampusId = null) {
      if (!campusSelect) return;
      try {
          const res = await fetch('api/campuses/all');
          const data = await res.json();
          if (data.success) {
              campusSelect.innerHTML = '<option value="" disabled selected>Select Campus</option>';
              data.campuses.forEach(campus => {
                  const option = document.createElement('option');
                  option.value = campus.campus_id;
                  option.textContent = campus.campus_name;
                  campusSelect.appendChild(option);
              });
              if (currentCampusId) {
                  campusSelect.value = currentCampusId;
              }
          }
      } catch (err) {
          console.error("Failed to load campuses:", err);
      }
  }

  function toggleEditMode(isEditing) {
    if (isEditing) {
      fields.forEach(id => {
        if(inputElements[id]) {
            // Special case for genderOther: only enable if genderSelect value is 'Other'
            if (id === 'genderOther' && genderSelect && genderSelect.value !== 'Other') return;

            inputElements[id].disabled = false;
            inputElements[id].classList.remove('bg-gray-50', 'border-gray-200');
            inputElements[id].classList.add('bg-white');
        }
      });
      formActions.classList.remove('hidden');
      editProfileBtn.classList.add('hidden');
    } else {
      fields.forEach(id => {
        if(inputElements[id]) {
            inputElements[id].disabled = true;
            inputElements[id].classList.add('bg-gray-50', 'border-gray-200');
            inputElements[id].classList.remove('bg-white');
            inputElements[id].value = originalFormState[id] || '';
        }
      });
      // Ensure genderOther visibility is reset based on loaded value
      if (genderSelect) {
        if (genderSelect.value === 'Other') {
            genderOtherInput.classList.remove('hidden');
        } else {
            genderOtherInput.classList.add('hidden');
        }
      }
      formActions.classList.add('hidden');
      editProfileBtn.classList.remove('hidden');
    }
  }

  async function loadProfile() {
    try {
      const res = await fetch('api/campus_admin/myProfile/get'); 
      const data = await res.json();

      if (!data.success) {
        showMessage(errorMessage, data.message);
        return;
      }

      const profile = data.profile;
      
      const profileName = `${profile.first_name || ''} ${profile.last_name || ''}`.trim();
      document.getElementById('profileName').textContent = profileName || profile.username;
      document.getElementById('profileStudentId').textContent = profile.username;
      
      await loadCampuses(profile.campus_id);

      // Gender Logic
      let genderValue = profile.gender || "";
      let genderOtherValue = "";
      const standardOptions = ["Male", "Female", "LGBTQIA+", "Prefer not to say", "Other"];

      if (standardOptions.includes(genderValue)) {
        // value remains genderValue
      } else if (genderValue) {
        genderOtherValue = genderValue;
        genderValue = "Other";
      }

      const dataMap = {
          'firstName': profile.first_name,
          'lastName': profile.last_name,
          'middleName': profile.middle_name,
          'suffix': profile.suffix,
          'email': profile.email,
          'gender': genderValue,
          'genderOther': genderOtherValue,
          'campus': profile.campus_id
      };
      
      fields.forEach(id => {
          const value = dataMap[id] || '';
          if(inputElements[id]) {
              inputElements[id].value = value;
          }
          originalFormState[id] = value; 
      });

      if (genderSelect && genderSelect.value === 'Other') {
        genderOtherInput.classList.remove('hidden');
      } else {
        genderOtherInput.classList.add('hidden');
      }


      if (profile.allow_edit === 1) {
        editProfileBtn.classList.remove('hidden');
      } else {
        profileLockedInfo.textContent = "Profile editing is locked.";
        profileLockedInfo.classList.remove('hidden');
      }

    } catch (err) {
      showMessage(errorMessage, 'Failed to load profile data.');
      console.error(err);
    }
  }

  editProfileBtn.addEventListener('click', () => toggleEditMode(true));
  cancelProfileBtn.addEventListener('click', () => toggleEditMode(false));

  profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    saveProfileBtn.disabled = true;
    saveProfileBtn.textContent = 'Saving...';

    const payload = {
        'first_name': document.getElementById("firstName").value,
        'last_name': document.getElementById("lastName").value,
        'middle_name': document.getElementById("middleName").value,
        'suffix': document.getElementById("suffix").value,
        'email': document.getElementById("email").value,
        'gender': document.getElementById("gender").value,
        'gender_other': document.getElementById("genderOther").value,
        'campus_id': document.getElementById("campus").value
    };

    try {
      const res = await fetch('api/campus_admin/myProfile/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (data.success) {
        showMessage(successMessage, data.message);
        
        const newName = `${payload.first_name} ${payload.last_name}`;
        document.getElementById('profileName').textContent = newName.trim();
        
        const sessionUserName = document.querySelector('#session-user-name');
        if(sessionUserName) sessionUserName.textContent = newName.trim();
        
        originalFormState = {
            'lastName': payload.last_name,
            'firstName': payload.first_name,
            'middleName': payload.middle_name,
            'suffix': payload.suffix,
            'email': payload.email,
            'gender': payload.gender,
            'genderOther': payload.gender_other,
            'campus': payload.campus_id
        };
        
        toggleEditMode(false); 
        
      } else {
        showMessage(errorMessage, data.message);
      }

    } catch (err) {
      showMessage(errorMessage, 'An error occurred. Please try again.');
      console.error(err);
    } finally {
      saveProfileBtn.disabled = false;
      saveProfileBtn.textContent = 'Save Changes';
    }
  });

  loadProfile();
});
