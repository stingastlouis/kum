<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Dashboard - Delicious Cake</title>
    <link rel="icon" type="image/png" sizes="512x512" href="../assets/img/spotlight.png">
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/Nunito.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/dropdown.css">
</head>

<body id="page-top">
    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-laugh-wink me-2 rotate-n-15"></i>
                <span>Delicious Cake</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="category.php">Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="role.php">Roles</a></li>
                    <li class="nav-item"><a class="nav-link" href="giftbox.php">Giftboxes</a></li>
                    <li class="nav-item"><a class="nav-link" href="cake.php">Cakes</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff.php">Staff</a></li>
                    <li class="nav-item"><a class="nav-link" href="delivery.php">Deliveries</a></li>
                    <li class="nav-item"><a class="nav-link" href="customer.php">Customers</a></li>
                </ul>
                <form class="d-flex me-3" role="search">
                    <input class="form-control form-control-sm me-2" type="search" placeholder="Search" aria-label="Search">
                    <button class="btn btn-sm btn-light" type="submit"><i class="fas fa-search"></i></button>
                </form>

                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="badge bg-danger badge-counter">3+</span>
                            <i class="fas fa-bell"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="alertsDropdown">
                            <li><h6 class="dropdown-header">Alerts Center</h6></li>
                            <li><a class="dropdown-item" href="#">New monthly report is ready</a></li>
                            <li><a class="dropdown-item" href="#">$290.29 deposited</a></li>
                            <li><a class="dropdown-item" href="#">High spending alert</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">Show All Alerts</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="badge bg-danger badge-counter">7</span>
                            <i class="fas fa-envelope"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="messagesDropdown">
                            <li><h6 class="dropdown-header">Message Center</h6></li>
                            <li><a class="dropdown-item" href="#">New message from Emily</a></li>
                            <li><a class="dropdown-item" href="#">Photos received</a></li>
                            <li><a class="dropdown-item" href="#">Monthly report looks good</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">Show All Messages</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-none d-lg-inline text-white small me-2">Valerie Luna</span>
                            <img class="img-profile rounded-circle" src="assets/img/avatars/avatar1.jpeg" width="30" height="30">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cogs fa-sm fa-fw me-2 text-gray-400"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-list fa-sm fa-fw me-2 text-gray-400"></i>Activity Log</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <br>