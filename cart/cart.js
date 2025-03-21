const cartStorageKey = "user-cart";

function loadCartData() {
  const cartData = localStorage.getItem(cartStorageKey);
  return cartData ? JSON.parse(cartData) : [];
}

function saveCartData(cart) {
  localStorage.setItem(cartStorageKey, JSON.stringify(cart));
}

function addProductToCart(id, name, price, quantity) {
  const cart = loadCartData();
  const existingProduct = cart.find((product) => product.id === id);

  if (existingProduct) {
    existingProduct.quantity += quantity;
  } else {
    cart.push({ id, name, price, quantity });
  }

  saveCartData(cart);
  updateCartDisplay(cart);
}

function removeProductFromCart(id) {
  let cart = loadCartData();
  cart = cart.filter((product) => product.id !== id);
  saveCartData(cart);
  updateCartDisplay(cart);
}

function updateCartDisplay(cart) {
  const cartContainer = document.getElementById("cart-container");
  const cartItems = document.getElementById("cart-items");
  const cartTotal = document.getElementById("cart-total");

  if (!cart) cart = loadCartData();

  cartItems.innerHTML = "";
  let total = 0;
  cart.forEach((product) => {
    const productTotal = product.price * product.quantity;
    total += productTotal;

    const listItem = document.createElement("li");
    listItem.classList.add(
      "list-group-item",
      "d-flex",
      "justify-content-between",
      "align-items-center"
    );
    listItem.innerHTML = `
            <div>
                <strong>${product.name}</strong><br>
                $${product.price.toFixed(2)} x ${product.quantity}
            </div>
            <div>
                <span>$${productTotal.toFixed(2)}</span>
                <button class="btn btn-danger btn-sm remove-from-cart" data-id="${
                  product.id
                }">Remove</button>
            </div>
        `;
    cartItems.appendChild(listItem);
  });

  cartTotal.textContent = `$${total.toFixed(2)}`;
  cartContainer.style.display = cart.length > 0 ? "block" : "none";
}

function initializeCart() {
  updateCartDisplay();

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("add-to-cart")) {
      const button = event.target;
      const productId = button.getAttribute("data-id");
      const productName = button.getAttribute("data-name");
      const productPrice = parseFloat(button.getAttribute("data-price"));
      const productStock = parseInt(button.getAttribute("data-stock"));
      const quantityInput = button
        .closest(".d-flex")
        .querySelector(".quantity-input");
      const quantity = parseInt(quantityInput.value);

      if (quantity > 0 && quantity <= productStock) {
        addProductToCart(productId, productName, productPrice, quantity);
      } else {
        alert("Invalid quantity!");
      }
    }
  });

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("remove-from-cart")) {
      const productId = event.target.getAttribute("data-id");
      removeProductFromCart(productId);
    }
  });
}

document
  .getElementById("checkout-button")
  .addEventListener("click", async () => {
    const cart = loadCartData();

    if (cart.length === 0) {
      alert("Your cart is empty!");
      return;
    }

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
  });

document.addEventListener("DOMContentLoaded", initializeCart);
