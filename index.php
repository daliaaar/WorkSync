<!DOCTYPE html>
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

.hero-section {
    background-color: #3b71ca;
    padding: 60px 5%;
    color: #ffffff;

}

.hero-section h1{
    font-size: 48px;
    margin-bottom: 15px;
    font-weight: bold;
}

.hero-section p{
    font-size: 22px;
    margin-bottom: 25px;
}

.mockup-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.mockup-box{
    height: 50px;
    background-color: #e9ecef;
    border: solid 1px #ced4da;
    border-radius: 5px;
}

.btn{
    background-color: #f8f9fa;
    color: black;
    font-weight: bold;
}
</style>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="/CSS/index.css"/>

    </head>
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

.hero-section {
    background-color: #3b71ca;
    padding: 60px 5%;
    color: #ffffff;

}

.hero-section h1{
    font-size: 48px;
    margin-bottom: 15px;
    font-weight: bold;
}

.hero-section p{
    font-size: 22px;
    margin-bottom: 25px;
}



.btn{
    background-color:aliceblue;
    font-weight: bold;
}

    </style>
    <body>
        <header class="top-header">
        <div class="logo-container">
            <a href="/">
                <img src="WorkSync.png" alt="Company Name Logo" class="logo-img" width="150" height="40">
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
        <section class="hero-section"> 
            <div class="container-fluid px-0">
                <div class="row align-items-center">
                    <div class="col-lg-6 hero-text mb-4 mb-lg-0">
                    <h1> Manage your office</h1>
                    <p>Simplify your workflow</p>
                    <div class="d-flex gap-3">
                    <a href="register.php" class="btn"> Sign up your company</a>
                    <a href="log_in.php" class="btn"> Log in</a>
                    </div>
                </div>

        <div class="col-lg-6">
            <div class="mockup-container">
                <div class="row g-0">
                    <div class="col-4 border-end pe-2">
                        <strong class="d-block mb-2">WorkSync</strong>
                        <ul class="list-unstyled small">
                            <li>Dashboard</li>
                            <li>Offices</li>
                            <li>Employees</li>
                            <li>Equipment</li>
                            <li>Reports</li>
                            <li>Settings</li>
                        </ul>
                    </div>
                    <div class="col-8 ps-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <input type="text" class="form-control form-control-sm w-50" placeholder="SEARCH">
                            <small><i class="bi bi-person"></i> Jens </small>
                        </div> 
                        <div class="fw-bold small border-bottom mb-2 pb-1">DASHBOARD</div>
                        <div class="row g-2">
                            <div class="col-6"><div class="mockup-box"></div></div>
                            <div class="col-6"><div class="mockup-box"></div></div>
                            <div class="col-6"><div class="mockup-box"></div></div>
                            <div class="col-6"><div class="mockup-box"></div></div>
                            <div class="col-6"><div class="mockup-box"></div></div>
                            <div class="col-6"><div class="mockup-box"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
        <section class="container my-5">
            <!--Card 1-->
            <h3 class="text-center fw-bold mb-4">Everything you need to run your workplace</h3>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">
                <div class="col">
                    <div class="bottom-card">
                        <i class="bi bi-building fs-3 me-2"></i>
                        <strong>Office management</strong>
                    </div>
                </div>
                <div class="col">
                    <div class="bottom-card">
                        <i class="bi bi-people fs-3 me-2"></i>
                        <strong>Employee management</strong>
                    </div>
                </div>
                <div class="col">
                    <div class="bottom-card">
                        <i class="bi bi-laptop fs-3 me-2"></i>
                        <strong>Equipment tracking</strong>
                    </div>
                </div>
                 <div class="col">
                    <div class="bottom-card">
                        <i class="bi bi-calendar-check fs-3 me-2"></i>
                        <strong>Reservations</strong>
                    </div>
                </div>
                 <div class="col">
                    <div class="bottom-card">
                        <i class="bi bi-graph-up fs-3 me-2"></i>
                        <strong>Reports and insights</strong>
                    </div>
                </div>
            </div>
        </section>
    </body>
</html> 