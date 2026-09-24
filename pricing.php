<!<!DOCTYPE html> 
<html lang="en">  
    <style>
      *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family:Arial, Helvetica, sans-serif;
   
}

body{
     background-color:white;
     min-height: 100vh;
}
.top-header{
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 5%;
    background-color: #ffffff;
    border-bottom: 1px solid #e5e5e5;
    z-index: 10;
    
}

.main-nav .nav-links {
    list-style: none;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 30px;
    margin: 0;
    padding: 0;
}



.nav-links li a{

    text-decoration: none;
    font-size: 18px;
    color:black;
    transition: 0.3s;
   

}
nav a:hover {
    color: #3b71ca;
}

nav a:active {
    color: black;
}

.pricing-card{
    transition: all 0.3s ease;
    border-radius: 15px;
    border: none;
    background: linear-gradient(145deg, #ffffff, #f5f5f5);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

.pricing-card:hover{
    transform: translateY(-10px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
}
.feature-list li{
    margin-bottom: 0.8rem;
}

.popular-badge{
    position: absolute;
    top: -12px;
    right: 20px; 
    border-radius: 20px;
}
.gradient-custom{
    background: linear-gradient(45deg, #42f5bf, #2b4fc4);
    border: none;
    padding: 12px 30px;
    transition: all 0.3s ease;
}
.gradient-custom:hover{
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4);
}
</style>
     <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="/CSS/pricing.css"/>

    </head>
    <body>
        <header class="top-header">
        <div class="logo-container">
            <a href="/">
                <img src="/Images/WorkSyncLogo.png" alt="Company Name Logo" class="logo-img" width="150" height="40">
            </a>
        </div>

        <nav class="main-nav">
            <ul class="nav-links">
                <li><a href="pricing.php">Pricing</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="log_in.php">Login</a></li>
                <li><a href="register.php">Sign up</a></li>
            </ul>
        </nav>
    </header>
    <div class="bg-light">
        <div class="container py-5">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="display-4 fw-bold">Choose your plan</h2>
                    <p class="text-muted">Select the plan that fits your needs</p>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col">
                    <div class="card h-100 pricing-card shadow-sm">
                        <div class="card-body p-5">
                            <h5 class="card-title text-muted text-uppercase mb-4">Basic Plan</h5>
                            <h1 class="display-5 mb-4">$9.99<small class="text-muted fw-light">/mo</small></h1>
                            <p class="text-muted mb-4" style="height: 48px;">Suitable for startup businesses</p>
                            <ul class="list-unstyled feature-list">
                                <li class="bi bi-check2 text-primary me-2">10 users</li>
                            </ul>
                            <button class="btn btn-outline-primary btn-lg w-100 mt-4">Get Started</button>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100 pricing-card shadow position-relative">
                        <span class="badge gradient-custom text-white popular-badge px-4 py-2">Popular</span>
                        <div class="card-body p-5">
                            <h5 class="card-title text-primary text-uppercase mb-4">Pro plan</h5>
                            <h1 class="display-5 mb-4">$19.99<small class="text-muted fw-light">/mo</small></h1>
                            <p class="text-muted mb-4" style="height: 48px;">Suitable for growing businesses</p>
                            <ul class="list-unstyled feature-list">
                                <li class="bi bi-check2 text-primary me-2">50 users</li>
                            </ul>
                            <button class="btn gradient-custom text-white btn-lg w-100 mt-4">Get Started</button>
                        </div>
                    </div>
                </div>
               <div class="col">
                    <div class="card h-100 pricing-card shadow-sm">
                        <div class="card-body p-5">
                            <h5 class="card-title text-muted text-uppercase mb-4">Enterprise Plan</h5>
                            <h1 class="display-5 mb-4">$49.99<small class="text-muted fw-light">/mo</small></h1>
                            <p class="text-muted mb-4" style="height: 48px;">Suitable for large businesses</p>
                            <ul class="list-unstyled feature-list">
                                <li class="bi bi-check2 text-primary me-2">Over 200 users</li>
                            </ul>
                            <button class="btn btn-outline-primary btn-lg w-100 mt-4">Get Started</button>
                        </div>
                    </div>
                </div>    
             </div>
        </div>
    </div>
</body>