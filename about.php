<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>About WorkSync</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            background:#f8f9fa;
        }

        .top-header{
            width:100%;
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:20px 5%;
            background:white;
            border-bottom:1px solid #e5e5e5;
        }

        .nav-links{
            list-style:none;
            display:flex;
            flex-direction:row;
            align-items:center;
            gap:30px;
            margin:0;
            padding:0;
        }

        .nav-links a{
            text-decoration:none;
            font-size:18px;
            color:black;
            transition:0.3s;
        }

        .nav-links a:hover{
            color:#3b71ca;
        }

        .hero{
            background:linear-gradient(135deg,#2b4fc4,#42f5bf);
            color:white;
            text-align:center;
            padding:100px 20px;
        }

        .hero h1{
            font-size:4rem;
            font-weight:bold;
        }

        .hero p{
            font-size:1.2rem;
            max-width:800px;
            margin:auto;
        }

        .section-card{
            border:none;
            border-radius:15px;
            background:white;
            box-shadow:0 6px 15px rgba(0,0,0,0.08);
            transition:0.3s;
        }

        .section-card:hover{
            transform:translateY(-5px);
        }

        .feature-icon{
            font-size:3rem;
            color:#3b71ca;
        }

        .gradient-text{
            background:linear-gradient(45deg,#42f5bf,#2b4fc4);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
    </style>

</head>

<body>

<header class="top-header">

    <div class="logo-container">
        <a href="/">
            <img src="WorkSync.png" alt="Company Name Logo" class="logo-img" width="150" height="40">
        </a>
    </div>

    <nav>
        <ul class="nav-links">
            <li><a href="pricing.php">Pricing</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="log_in.php">Login</a></li>
            <li><a href="register.php">Sign up</a></li>
        </ul>
    </nav>

</header>

<section class="hero">
    <h1>About WorkSync</h1>
    <p>
        Simplifying workplace management through one centralized platform.
        Manage employees, meeting rooms, equipment and tasks with ease.
    </p>
</section>

<div class="container py-5">

    <div class="row mb-5">

        <div class="col-lg-8 mx-auto text-center">
            <h2 class="gradient-text fw-bold mb-4">
                Who We Are
            </h2>

            <p class="lead text-muted">
                WorkSync is a workplace management solution designed to help
                businesses manage employees, meeting rooms, equipment and daily
                operations through a single, easy-to-use platform.
            </p>

        </div>

    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card section-card h-100">
                <div class="card-body text-center p-4">

                    <h3 class="feature-icon">👥</h3>

                    <h4 class="mt-3">
                        Employee Management
                    </h4>

                    <p class="text-muted">
                        Keep employee information organized and easily accessible.
                    </p>

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card section-card h-100">
                <div class="card-body text-center p-4">

                    <h3 class="feature-icon">🏢</h3>

                    <h4 class="mt-3">
                        Room Booking
                    </h4>

                    <p class="text-muted">
                        Reserve meeting rooms and manage workspace availability.
                    </p>

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card section-card h-100">
                <div class="card-body text-center p-4">

                    <h3 class="feature-icon">📋</h3>

                    <h4 class="mt-3">
                        Task Management
                    </h4>

                    <p class="text-muted">
                        Assign work, track progress and improve collaboration.
                    </p>

                </div>
            </div>
        </div>

    </div>

    <div class="row mt-5">

        <div class="col-lg-10 mx-auto">

            <div class="card section-card">

                <div class="card-body p-5">

                    <h2 class="text-center mb-4">
                        Our Mission
                    </h2>

                    <p class="text-center text-muted">
                        Our mission is to improve productivity and workplace
                        organization by bringing essential business tools into
                        one secure and efficient platform.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<footer class="bg-dark text-white text-center py-4">
    <p class="mb-0">
        © 2026 WorkSync. All Rights Reserved.
    </p>
</footer>

</body>
</html>