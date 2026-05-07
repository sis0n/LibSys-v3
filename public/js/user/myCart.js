let cart = [];
let checkedMap = {}; 

async function loadCart() {
    try {
        const res = await fetch("api/staff/cart/json");
        if (!res.ok) throw new Error("Failed to load cart");
        cart = await res.json();
        renderCart();
    } catch (err) {
        console.error("Error loading cart:", err);
    }
}

// === 1. CHECKOUT CART ===
async function checkoutCart() {
    console.log("Checkout button clicked");
    const selectedIds = Object.keys(checkedMap).filter(id => checkedMap[id]);
    console.log("Selected IDs for checkout:", selectedIds);

    if (selectedIds.length === 0) {
        // 🟠 Warning Toast for no selected items
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
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 text-orange-600">
                            <i class="ph ph-warning text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-semibold text-orange-600">No Items Selected</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">Please select at least one item to checkout.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
            },
        });
        return;
    }

    // 🟠 Loading Animation habang nagche-checkout
    Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[14px]">Processing Checkout...<br><span class="text-sm text-gray-500">Please wait.</span></p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
        },
    });

    try {
        const res = await fetch("api/staff/cart/checkout", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ cart_ids: selectedIds })
        });

        const text = await res.text();
        Swal.close(); // Close loading modal

        let data;
        try {
            data = JSON.parse(text);
        } catch {
            console.warn("Response was not JSON, maybe redirected or HTML page.");
            document.open();
            document.write(text);
            document.close();
            return;
        }

        console.log("Parsed data:", data);

        const duration = 3000; // 3 seconds timer
        let timerInterval;

        const showCustomAlert = (isSuccess, title, message) => {
            const theme = isSuccess ? {
                bg: 'bg-green-50',
                border: 'border-green-300',
                text: 'text-green-700',
                iconBg: 'bg-green-100',
                iconColor: 'text-green-600',
                iconClass: 'ph-check-circle',
                progressBarColor: 'bg-green-500',
                title: 'Checkout Successful!'
            } : {
                bg: 'bg-red-50',
                border: 'border-red-300',
                text: 'text-red-700',
                iconBg: 'bg-red-100',
                iconColor: 'text-red-600',
                iconClass: 'ph-x-circle',
                progressBarColor: 'bg-red-500',
                title: 'Checkout Failed'
            };

            Swal.fire({
                showConfirmButton: false, 
                showCancelButton: false,
                buttonsStyling: false,
                
                backdrop: `rgba(0,0,0,0.3) backdrop-filter: blur(6px)`,
                timer: duration, 
                
                didOpen: () => {
                    const progressBar = Swal.getHtmlContainer().querySelector("#progress-bar");
                    let width = 100;
                    timerInterval = setInterval(() => {
                        // Compute step based on duration
                        width -= 100 / (duration / 100); 
                        if (progressBar) {
                            progressBar.style.width = width + "%";
                        }
                    }, 100);
                },
                willClose: () => {
                    clearInterval(timerInterval);
                },

                html: `
                    <div id="checkoutModal" class="w-[450px] ${theme.bg} border-2 ${theme.border} rounded-2xl p-8 shadow-xl text-center">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full ${theme.iconBg} mx-auto mb-4">
                            <i class="ph ${theme.iconClass} ${theme.iconColor} text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold ${theme.text}">${theme.title}</h3>
                        <p class="text-base ${theme.text} mt-3 mb-4">
                            ${message}
                        </p>
                        <div class="w-full bg-gray-200 h-2 rounded mt-4 overflow-hidden">
                            <div id="progress-bar" class="${theme.progressBarColor} h-2 w-full transition-all duration-100 ease-linear"></div>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "!block !bg-transparent !shadow-none !p-0 !border-0 !w-auto !min-w-0 !max-w-none",
                },
            });
        };


        if (data.success) {
            showCustomAlert(true, 'Checkout Successful', "You can now view your QR Borrowing Ticket in the Borrowing Ticket page.");
            checkedMap = {};
            loadCart();
        } else {
            // --- REDIRECT TO PROFILE IF INCOMPLETE ---
            if (data.message && data.message.includes("Profile details are incomplete")) {
                Swal.fire({
                    background: "transparent",
                    html: `
                        <div class="flex flex-col text-center">
                            <div class="flex justify-center mb-3">
                                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-600">
                                    <i class="ph ph-user-circle-gear text-3xl"></i>
                                </div>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Incomplete Profile</h3>
                            <p class="text-[14px] text-gray-700 mt-1">${data.message}</p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: "Go to Profile",
                    cancelButtonText: "Cancel",
                    customClass: {
                        popup: "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",
                        confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",
                        cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",
                        actions: "!mt-4"
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'myProfile';
                    }
                });
            } else {
                // --- Fallback for other errors ---
                showCustomAlert(false, 'Checkout Failed', data.message || "An error occurred during checkout.");
            }
        }

    } catch (err) {
        Swal.close(); // Ensure loading closes on error
        console.error("Checkout error:", err);
        // 🔴 Generic Network Error Toast 
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
                            <h3 class="text-[15px] font-semibold text-red-600">Network Error</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">Something went wrong. Please check your connection and try again.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
            },
        });
    }
}


// === 2. CLEAR CART ===
async function clearCart() {
    // 🟠 Confirmation Modal
    const confirmationResult = await Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col text-center">
                <div class="flex justify-center mb-3">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
                        <i class="ph ph-trash text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-[17px] font-semibold text-orange-700">Clear Cart?</h3>
                <p class="text-[14px] text-gray-700 mt-1">
                    Are you sure you want to remove all items from your cart? This action cannot be undone.
                </p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Yes, Clear All!",
        cancelButtonText: "Cancel",
        customClass: {
            popup:
                "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400 shadow-[0_0_8px_#ffb34770]",
            confirmButton:
                "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700",
            cancelButton:
                "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300",
        },
    });

    if (!confirmationResult.isConfirmed) {
        // ❌ Cancel toast
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 2000,
            width: "360px", 
            background: "transparent",
            html: `
                <div class="flex flex-col text-left">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
                            <i class="ph ph-x-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-semibold text-red-600">Cancelled</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">The cart was not cleared.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
            },
        });
        return;
    }

    // 🟠 Loading Animation
    Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[14px]">Clearing cart...<br><span class="text-sm text-gray-500">Just a moment.</span></p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
        },
    });

    try {
        const res = await fetch("api/staff/cart/clear", {
            method: "POST"
        });
        await new Promise(r => setTimeout(r, 300)); // Simulate delay
        Swal.close();

        if (!res.ok) throw new Error("Failed to clear cart");
        cart = [];
        checkedMap = {};
        
        // 🟢 Success Toast
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 2000,
            width: "360px", 
            background: "transparent",
            html: `
                <div class="flex flex-col text-left">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600">
                            <i class="ph ph-check-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-semibold text-green-600">Cart Cleared!</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">All items have been removed.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
            },
        });
        renderCart();
    } catch (err) {
        Swal.close();
        console.error(err);
        // 🔴 Error Toast
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
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
                            <i class="ph ph-x-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-semibold text-red-600">Clear Failed</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">An error occurred while clearing the cart.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
            },
        });
    }
}


// === 3. REMOVE FROM CART ===
async function removeFromCart(cartId) {
    // 🟠 Confirmation Modal (Remove Single Item)
    const confirmationResult = await Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col text-center">
                <div class="flex justify-center mb-3">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
                        <i class="ph ph-trash text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-[17px] font-semibold text-orange-700">Remove Item?</h3>
                <p class="text-[14px] text-gray-700 mt-1">
                    Are you sure you want to remove this item from your cart?
                </p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Yes, Remove",
        cancelButtonText: "Cancel",
        customClass: {
            popup:
                "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400 shadow-[0_0_8px_#ffb34770]",
            confirmButton:
                "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700",
            cancelButton:
                "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300",
        },
    });

    if (!confirmationResult.isConfirmed) {
        return;
    }
    
    // 🟠 Loading Animation
    Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[14px]">Removing item...<br><span class="text-sm text-gray-500">Just a moment.</span></p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
        },
    });


    try {
        const res = await fetch(`api/staff/cart/remove/${cartId}`, {
            method: "POST"
        });
        await new Promise(r => setTimeout(r, 300)); // Simulate delay
        Swal.close();

        if (!res.ok) throw new Error("Failed to remove item");
        cart = cart.filter(item => item.cart_id !== cartId);
        delete checkedMap[cartId]; 
        
        // 🟢 Success Toast
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 2000,
            width: "360px", 
            background: "transparent",
            html: `
                <div class="flex flex-col text-left">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600">
                            <i class="ph ph-check-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-semibold text-green-600">Item Removed!</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">The item was removed from your cart.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
            },
        });
        renderCart();
    } catch (err) {
        Swal.close();
        console.error(err);
        // 🔴 Error Toast
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
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
                            <i class="ph ph-x-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-semibold text-red-600">Removal Failed</h3>
                            <p class="text-[13px] text-gray-700 mt-0.5">An error occurred while removing the item.</p>
                        </div>
                    </div>
                </div>
            `,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
            },
        });
    }
}

// === RENDER AND UTILITY FUNCTIONS ===

function renderCart() {
    const emptyState = document.getElementById("empty-state");
    const cartItemsDiv = document.getElementById("cart-items");
    const cartCount = document.getElementById("cart-count");
    const itemsContainer = document.getElementById("selected-items");
    const selectAllCheckbox = document.getElementById("select-all");
    const selectedItemsSection = document.getElementById("selected-items-section");

    while (itemsContainer.firstChild) {
        itemsContainer.removeChild(itemsContainer.firstChild);
    }

    if (cart.length > 0) {
        emptyState.classList.add("hidden");
        cartItemsDiv.classList.remove("hidden");
        selectedItemsSection.classList.remove("hidden");

        cartCount.textContent = `${cart.length} total item(s)`;
        const cartIcon = document.createElement("i");
        cartIcon.className = "ph ph-shopping-cart text-sm";
        cartCount.insertBefore(cartIcon, cartCount.firstChild);

        cart.forEach(item => {
            const itemDiv = document.createElement("div");
            itemDiv.className =
                "mt-4 border rounded-lg border-gray-300 bg-white shadow-sm flex items-center justify-between p-4 transition cursor-pointer";

            const leftDiv = document.createElement("div");
            leftDiv.className = "flex items-center gap-3 w-full";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "w-4 h-4 accent-orange-600 cursor-pointer rounded-full";
            checkbox.dataset.type = item.type;
            checkbox.dataset.id = item.cart_id;

            // Initialize checkedMap if not present
            if (checkedMap[item.cart_id] === undefined) {
                 checkedMap[item.cart_id] = false;
            }

            if (checkedMap[item.cart_id]) {
                checkbox.checked = true;
                toggleHighlight(itemDiv, true);
            }

            const iconDiv = document.createElement("div");
            iconDiv.className = "flex-shrink-0 w-20 h-20 flex items-center justify-center";
            const iconEl = document.createElement("i");
            iconEl.className =
                `ph ${item.icon || 'ph-book'} text-5xl text-amber-700 w-10 h-10 leading-[2rem] text-center`;
            iconDiv.appendChild(iconEl);

            const infoDiv = document.createElement("div");
            infoDiv.className = "flex-1";

            const titleEl = document.createElement("h4");
            titleEl.className = "font-semibold";
            titleEl.textContent = item.title;
            infoDiv.appendChild(titleEl);

            if (item.author) {
                const authorEl = document.createElement("p");
                authorEl.className = "text-sm text-gray-600";
                authorEl.textContent = `by ${item.author}`;
                infoDiv.appendChild(authorEl);
            }

            if (item.accessionNumber || item.callNumber || item.subject) {
                const infoWrap = document.createElement("div");
                infoWrap.className = "flex flex-wrap gap-x-6 text-sm text-gray-600 mt-4";

                if (item.accessionNumber) {
                    const span = document.createElement("span");
                    const strong = document.createElement("span");
                    strong.className = "font-semibold";
                    strong.textContent = "Accession Number: ";
                    span.appendChild(strong);
                    span.appendChild(document.createTextNode(item.accessionNumber));
                    infoWrap.appendChild(span);
                }
                if (item.callNumber) {
                    const span = document.createElement("span");
                    const strong = document.createElement("span");
                    strong.className = "font-semibold";
                    strong.textContent = "Call Number: ";
                    span.appendChild(strong);
                    span.appendChild(document.createTextNode(item.callNumber));
                    infoWrap.appendChild(span);
                }
                if (item.subject) {
                    const span = document.createElement("span");
                    const strong = document.createElement("span");
                    strong.className = "font-semibold";
                    strong.textContent = "Subject: ";
                    span.appendChild(strong);
                    span.appendChild(document.createTextNode(item.subject));
                    infoWrap.appendChild(span);
                }

                infoDiv.appendChild(infoWrap);
            }

            leftDiv.appendChild(checkbox);
            leftDiv.appendChild(iconDiv);
            leftDiv.appendChild(infoDiv);

            const removeBtn = document.createElement("button");
            removeBtn.className = "text-2xl text-gray-800 hover:text-orange-700 transition ml-4";
            const removeIcon = document.createElement("i");
            removeIcon.className = "ph ph-trash";
            removeBtn.appendChild(removeIcon);
            removeBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                removeFromCart(item.cart_id);
            });

            itemDiv.addEventListener("click", () => {
                checkbox.checked = !checkbox.checked;
                checkedMap[item.cart_id] = checkbox.checked;
                toggleHighlight(itemDiv, checkbox.checked);
                updateSummary();
                syncSelectAll();
            });

            checkbox.addEventListener("click", (e) => {
                e.stopPropagation();
                checkedMap[item.cart_id] = checkbox.checked;
                toggleHighlight(itemDiv, checkbox.checked);
                updateSummary();
                syncSelectAll();
            });

            itemDiv.appendChild(leftDiv);
            itemDiv.appendChild(removeBtn);
            itemsContainer.appendChild(itemDiv);
        });

        updateSummary();
        syncSelectAll();

    } else {
        emptyState.classList.remove("hidden");
        cartItemsDiv.classList.add("hidden");
        selectedItemsSection.classList.add("hidden");
        cartCount.textContent = "0 total items";
        const cartIcon = document.createElement("i");
        cartIcon.className = "ph ph-shopping-cart text-sm";
        cartCount.insertBefore(cartIcon, cartCount.firstChild);
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.onchange = () => {
            const allItems = document.querySelectorAll("#selected-items input[type='checkbox']");
            allItems.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
                checkedMap[cb.dataset.id] = cb.checked;
                toggleHighlight(cb.closest("div.mt-4"), cb.checked);
            });
            updateSummary();
        };
    }
}

function toggleHighlight(itemDiv, checked) {
    itemDiv.classList.toggle("bg-orange-100", checked);
    itemDiv.classList.toggle("border-orange-500", checked);
}

function updateSummary() {
    const summaryText = document.getElementById("summary-text");
    const allItems = document.querySelectorAll("#selected-items input[type='checkbox']");
    let books = 0,
        equipment = 0;

    allItems.forEach(cb => {
        if (cb.checked) {
            if (cb.dataset.type === "book") books++;
            if (cb.dataset.type === "equipment") equipment++;
        }
    });

    summaryText.textContent =
        `${books} book(s) and ${equipment} equipment item(s) selected for borrowing`;
}

function syncSelectAll() {
    const selectAllCheckbox = document.getElementById("select-all");
    const allItems = document.querySelectorAll("#selected-items input[type='checkbox']"); 
    if (allItems.length > 0) {
        selectAllCheckbox.checked = [...allItems].every(cb => cb.checked);
    } else {
        selectAllCheckbox.checked = false;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    loadCart();
    const clearBtn = document.getElementById("clear-cart-btn");
    const checkoutBtn = document.getElementById("checkout-btn");

    if (clearBtn) clearBtn.addEventListener("click", clearCart);
    else console.error("Missing #clear-cart-btn");

    if (checkoutBtn) checkoutBtn.addEventListener("click", checkoutCart);
    else console.error("Missing #checkout-btn");
});