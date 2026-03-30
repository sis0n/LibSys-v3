document.addEventListener("DOMContentLoaded", function () {
  const policyCardsContainer = document.getElementById("policyCardsContainer");
  const editPolicyModal = document.getElementById("editPolicyModal");
  const editPolicyForm = document.getElementById("editPolicyForm");
  const campusFilter = document.getElementById("campusFilter");

  let allPolicies = [];

  const showToast = (icon, title, text, theme) => {
    if (typeof Swal === "undefined") return alert(text);

    const themeMap = {
      success: {
        color: "text-green-600",
        bg: "bg-green-100",
        border: "!border-green-400",
      },
      error: {
        color: "text-red-600",
        bg: "bg-red-100",
        border: "!border-red-400",
      },
    };
    const selected = themeMap[theme] || themeMap.success;

    Swal.fire({
      toast: true,
      position: "bottom-end",
      showConfirmButton: false,
      timer: 3000,
      background: "transparent",
      html: `
                <div class="flex items-center gap-3 p-1">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full ${selected.bg} ${selected.color}">
                        <i class="ph ph-${icon} text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold ${selected.color}">${title}</h3>
                        <p class="text-xs text-gray-600">${text}</p>
                    </div>
                </div>
            `,
      customClass: {
        popup: `!rounded-xl !shadow-lg !border-2 !p-2 !bg-white ${selected.border}`,
      },
    });
  };

  const loadPolicies = async () => {
    // Siguraduhin na may default na 1 kung empty ang filter value
    const campusId = campusFilter.value || "1";
    try {
      const res = await fetch(`${BASE_URL}/api/campus_admin/libraryPolicies/getAll?campus_id=${campusId}`);
      const data = await res.json();

      if (data.success) {
        allPolicies = data.policies;
        renderPolicies();
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      console.error("Error loading policies:", err);
      policyCardsContainer.innerHTML = `
                <div class="col-span-full py-10 text-center bg-red-50 rounded-xl border border-red-200">
                    <i class="ph ph-warning-circle text-red-500 text-4xl mb-2"></i>
                    <p class="text-red-700 font-medium">Failed to load policies</p>
                </div>
            `;
    }
  };

  const renderPolicies = () => {
    if (allPolicies.length === 0) {
      policyCardsContainer.innerHTML =
        '<p class="col-span-full text-center text-gray-500 py-20 bg-white rounded-xl border border-gray-100">No policies found for this campus.</p>';
      return;
    }

    policyCardsContainer.innerHTML = allPolicies
      .map(
        (policy) => `
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow text-left group">
                <div class="px-6 py-4" style="background: ${getRoleGradient(policy.role)}">
                    <div class="flex items-center gap-3 text-white">
                        <i class="ph ${getRoleIcon(policy.role)} text-3xl"></i>
                        <h3 class="text-xl font-bold capitalize">${policy.role}</h3>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg group-hover:bg-orange-50/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-books text-orange-600 text-2xl"></i>
                            <span class="text-gray-600 font-medium text-sm md:text-base">Borrow Limit</span>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">${policy.max_books} <small class="text-xs text-gray-500 font-normal uppercase">${policy.role === "equipment" ? "Items" : "Books"}</small></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg group-hover:bg-blue-50/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-calendar-clock text-blue-600 text-2xl"></i>
                            <span class="text-gray-600 font-medium text-sm md:text-base">Duration</span>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">${policy.borrow_duration_days === 0 ? "Same Day" : policy.borrow_duration_days} <small class="text-xs text-gray-500 font-normal uppercase">${policy.borrow_duration_days === 0 ? "" : "Days"}</small></span>
                    </div>
                    <div class="pt-2 text-[10px] text-gray-400 italic">
                        Last updated: ${new Date(policy.updated_at).toLocaleString()}
                    </div>
                    
                    <button class="edit-policy-btn w-full mt-4 flex items-center justify-center gap-2 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-orange-600 hover:border-orange-600 hover:text-white transition-all font-semibold shadow-sm" data-role="${policy.role}">
                        <i class="ph ph-pencil-simple"></i>
                        Edit Policy
                    </button>
                </div>
            </div>
        `,
      )
      .join("");

    document.querySelectorAll(".edit-policy-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const role = btn.dataset.role;
        openEditModal(role);
      });
    });
  };

  const getRoleIcon = (role) => {
    switch (role) {
      case "student":
        return "ph-student";
      case "faculty":
        return "ph-chalkboard-teacher";
      case "staff":
        return "ph-user-gear";
      case "equipment":
        return "ph-desktop-tower";
      default:
        return "ph-user";
    }
  };

  const getRoleGradient = (role) => {
    switch (role) {
      case "student":
        return "linear-gradient(135deg, #10b981 0%, #0d9488 100%)";
      case "faculty":
        return "linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%)";
      case "staff":
        return "linear-gradient(135deg, #f97316 0%, #d97706 100%)";
      case "equipment":
        return "linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)";
      default:
        return "linear-gradient(135deg, #6b7280 0%, #4b5563 100%)";
    }
  };

  const openEditModal = (role) => {
    const policy = allPolicies.find((p) => p.role === role);
    if (!policy) return;

    document.getElementById("policyRole").value = policy.role;
    document.getElementById("displayRole").value = policy.role;
    document.getElementById("maxBooks").value = policy.max_books;

    const durationInput = document.getElementById("durationDays");
    durationInput.value = policy.borrow_duration_days;
    durationInput.setAttribute("min", role === "equipment" ? "0" : "1");

    const campusName = campusFilter.options[campusFilter.selectedIndex].text;
    document.getElementById("modalTitle").textContent =
      `Edit ${policy.role.charAt(0).toUpperCase() + policy.role.slice(1)} Policy (${campusName})`;

    editPolicyModal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  };

  window.closePolicyModal = () => {
    editPolicyModal.classList.add("hidden");
    document.body.style.overflow = "";
  };

  editPolicyForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
      role: document.getElementById("policyRole").value,
      max_books: document.getElementById("maxBooks").value,
      borrow_duration_days: document.getElementById("durationDays").value,
      campus_id: campusFilter.value,
    };

    Swal.fire({
      title: "Updating policy...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      const res = await fetch(`${BASE_URL}/api/campus_admin/libraryPolicies/update`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const result = await res.json();

      if (result.success) {
        await loadPolicies();
        closePolicyModal();
        showToast(
          "check-circle",
          "Success",
          "Library policy has been updated.",
          "success",
        );
      } else {
        throw new Error(result.message);
      }
    } catch (err) {
      console.error("Update error:", err);
      showToast(
        "warning-circle",
        "Update Failed",
        err.message || "An error occurred.",
        "error",
      );
    }
  });

  campusFilter.addEventListener("change", loadPolicies);

  loadPolicies();
});
