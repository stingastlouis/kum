<?php include "includes/header.php"; ?>
<link rel="stylesheet" href="./assets/css/profile.css">

<div class="container py-4">
    <h1 class="text-center mb-4">Welcome to Your Cake Profile </h1>

    <div class="profile-tabs mb-4">
        <ul class="nav nav-tabs justify-content-center" id="profileTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="user-info-tab" data-bs-toggle="tab" data-bs-target="#user-info" type="button" role="tab">User Info</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="order-history-tab" data-bs-toggle="tab" data-bs-target="#order-history" type="button" role="tab">Order History</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="queries-tab" data-bs-toggle="tab" data-bs-target="#queries" type="button" role="tab">Queries</button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="profileTabContent">
        <?php include "./user-profile/info.php"; ?>
        <?php include "./user-profile/order-list.php"; ?>
        <?php include "./user-profile/message.php"; ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hash = window.location.hash;
        if (hash === "#order-history" || hash === "#queries") {
            const tabTrigger = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        }
    });
</script>

<?php include "includes/footer.php"; ?>