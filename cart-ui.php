<link rel="stylesheet" href="./assets/css/cart-ui.css">
<div id="cart-container" class="cake-cart shadow-lg">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="text-pink fw-bold mb-0"> Your Cake Cart</h5>
    <button id="close-cart" class="btn-close-custom">✖</button>
  </div>

  <ul id="cart-items" class="list-group mb-3"></ul>

  <div class="d-flex justify-content-between mb-3">
    <strong class="text-pink">Total:</strong>
    <span id="cart-total" class="fw-bold text-dark">$ 0.00</span>
  </div>

  <button id="checkout-button" class="btn btn-pink w-100 rounded-pill"> Checkout</button>
</div>

<script src="./cart/cart.js"></script>