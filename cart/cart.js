const cartKey = "cake-cart";

function loadCart() {
  const cartData = localStorage.getItem(cartKey);
  return cartData ? JSON.parse(cartData) : [];
}

function saveCart(cart) {
  localStorage.setItem(cartKey, JSON.stringify(cart));
}

function addToCart(id, name, price, quantity, type) {
  const cart = loadCart();
  const existingItem = cart.find(
    (item) =>
      String(item.id) === String(id) && String(item.type) === String(type)
  );

  if (existingItem) {
    existingItem.quantity += quantity;
  } else {
    cart.push({ id: String(id), name, price, quantity, type: String(type) });
  }

  saveCart(cart);
  updateCartUI(cart);
}

function removeFromCart(id, type) {
  let cart = loadCart();
  cart = cart.filter((item) => {
    const match = !(String(item.id) === String(id) && item.type === type);
    return match;
  });

  saveCart(cart);
  updateCartUI(cart);
}

function updateCartUI(cart) {
  const cartContainer = document.getElementById("cart-container");
  const cartItems = document.getElementById("cart-items");
  const cartTotal = document.getElementById("cart-total");

  if (!cart) cart = loadCart();

  if (cartItems) cartItems.innerHTML = "";

  let total = 0;
  if (cart.length === 0) {
    const emptyMessage = document.createElement("li");
    emptyMessage.classList.add("list-group-item", "text-center", "text-muted");
    emptyMessage.textContent = "Your cart is empty.";
    cartItems.appendChild(emptyMessage);
  } else {
    cart.forEach((item) => {
      const itemTotal = item.price * item.quantity;
      total += itemTotal;

      const listItem = document.createElement("li");
      listItem.classList.add(
        "list-group-item",
        "d-flex",
        "justify-content-between",
        "align-items-center"
      );
      listItem.innerHTML = `
        <div>
          <strong>${item.name} (${item.type})</strong><br>
          $ ${item.price.toFixed(2)} x ${item.quantity}
        </div>
        <div>
          <span>$ ${itemTotal.toFixed(2)}</span>
          <button class="btn btn-danger btn-sm remove-from-cart" data-id="${
            item.id
          }" data-type="${item.type}">Remove</button>
        </div>
      `;
      cartItems.appendChild(listItem);
    });
  }

  if (cartTotal) {
    cartTotal.textContent = `$ ${total.toFixed(2)}`;
  }

  updateCartIconCount();
}

function updateCartIconCount() {
  const cart = loadCart();
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

  const cartIcon = document.getElementById("cart-icon");
  const cartCount = document.getElementById("cart-count");

  if (cartIcon && cartCount) {
    cartCount.textContent = totalItems;
  }
}

function initCart() {
  updateCartUI();

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("add-to-cart")) {
      const button = event.target;
      const id = button.getAttribute("data-id");
      const name = button.getAttribute("data-name");
      const price = parseFloat(button.getAttribute("data-price"));
      const type = button.getAttribute("data-type");
      const stock = parseInt(button.getAttribute("data-stock"));
      const quantityInput = button
        .closest(".d-flex")
        .querySelector(".quantity-input");
      const quantity = parseInt(quantityInput.value);

      if (quantity > 0 && quantity <= stock) {
        addToCart(id, name, price, quantity, type);
        quantityInput.value = 1;
      } else {
        alert("Invalid quantity!");
      }
    }
  });

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("remove-from-cart")) {
      const id = event.target.getAttribute("data-id");
      const type = event.target.getAttribute("data-type");
      removeFromCart(id, type);
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initCart();
  updateCartIconCount();

  const cartIconButton = document.getElementById("cart-icon");
  const floatingCart = document.getElementById("cart-container");

  if (cartIconButton && floatingCart) {
    cartIconButton.addEventListener("click", (e) => {
      e.preventDefault();
      floatingCart.style.display = "block";
    });
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const closeBtn = document.getElementById("close-cart");
  const floatingCart = document.getElementById("cart-container");

  if (closeBtn && floatingCart) {
    closeBtn.addEventListener("click", () => {
      floatingCart.style.display = "none";
    });
  }
});

const checkoutButton = document.getElementById("checkout-button");
if (checkoutButton) {
  checkoutButton.addEventListener("click", async () => {
    const cart = loadCart();

    if (cart.length === 0) {
      alert("Your cart is empty!");
      return;
    }

    try {
      const response = await fetch("saveCart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ cart }),
      });

      const result = await response.json();

      if (result.success) {
        window.location.href = "checkout.php";
      } else {
        alert("Error: " + result.message);
      }
    } catch (error) {
      alert("An error occurred: " + error.message);
    }
  });
}
