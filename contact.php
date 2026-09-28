<?php

if($_SERVER["REQUEST_METHOD"] == "POST")
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $message = $_POST['message'];

    $mailheader = "From: ".$name." <".$email.">\r\n";

    $recipient = "worksync@gmail.com";
    if(mail($recipient, $company, $message, $mailheader))
    {
        echo "<script>alert('Message sent successfully!');</script>";

    }
    else
    {
            echo "<script>alert('Message not sent!');</script>";
    }
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
            <style>
        *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family:Arial, Helvetica, sans-serif;
   
}
body{
     background: linear-gradient(to bottom, 45deg, #42f5bf 50%, #2b4fc4 50%);
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

.form-control-custom{
    border: none;
    border-bottom: 1px solid #ced4da;
    border-radius: 0;
    padding-left: 0;
    padding-right: 0;
    background-color: transparent;
}

.form-control-custom:focus {
    outline: none;
    box-shadow: none;
    border-color: #3b71ca;
}

.form-label-custom{
    font-size: 0.9 rem;
    color: #888;
    margin-bottom: 0.2rem;
    display: block;
}

.bg-custom-dark{
    background-color: #3b71ca;
    color: #fff;
}

.btn-custom{
    background-color: #3b71ca;
    color: #fff;
    border-radius: 0;

}
</style>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="/CSS/contact.css"/>

    </head>
   
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
        <main class="container my-5">
            <div class="row justify-content-center">
                <div class="col-xl-9">

                    <div class="text-center mb-5">
                        <h1 class="fw-bold tracking-tight mb-2" style="font-size: 2.5rem;">Get in touch </h1>
                        <p class="text-secondary fs-5">Our team is always here to help you.</p>
                    </div>
                        <div class="card high-end-card rounded-4 overflow-hidden bg-white">
                            <div class="row g-0">

                                <div class="col-lg-7 p-4 p-md-5">
                                 <form action="contact.php" method="POST">

                                <div class="mb-3">
                                    <label for="nameInput" class="form-label text-secondary small fw-semibold text-uppercase tracking-wider">Name</label>
                                    <input type="text" id="nameInput" name="name" class="form-control rounded-3" placeholder="Your name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="emailInput" class="form-label text-secondary small fw-semibold text-uppercase tracking-wider">Email</label>
                                    <input type="email" id="emailInput" name="email" class="form-control rounded-3" placeholder="Your email" required>
                                </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="phoneInput" class="form-label text-secondary small fw-semibold text-uppercase tracking-wider">Phone</label>
                                    <input type="text" id="phoneInput" name="phone" class="form-control rounded-3" placeholder="Phone number" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="companyInput" class="form-label text-secondary small fw-semibold text-uppercase tracking-wider">Company</label>
                                    <input type="text" id="companyInput" name="company" class="form-control rounded-3" placeholder="Company name" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="messageInput" class="form-label text-secondary small fw-semibold text-uppercase tracking-wider">Message</label>
                                <textarea name="message" id="messageInput" class="form-control rounded-3" placeholder="Tell us what we can help you with..." rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 py-3 rounded-3 fw-semibold text-white" style="background-color:#3b71ca; color: white;">Send Message</button>
                        </form>
                    </div>

                    <div class="col-lg-5 p-4 p-md-5 bg-custom-dark text-white d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="fw-bold mb-3">Contact Information</h4>
                            <p class="text-white-50 mb-5 fs-6 " style="line-height: 1.6;;">Feel free to reach out to us using the contact information below or send us a message. We are here to assist you.</p>
                            <ul class="list-unstyled d-flex flex-column gap-4">
                                <li class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-25 p-2 rounded-3 me-3 text-white">
                                        <i class="bi bi-geo-alt"></i>
                                    </div>
                                    <span class="text-white-50 fs-6">Kjølnes Ring 56, 3918 Porsgrunn</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-25 p-2 rounded-3 me-3 text-white">
                                        <i class="bi bi-telephone"></i>
                                    </div>
                                    <a href="tel:+4712345678" class="text-white-50 fs-6 text-decoration-none hover-white">+47 123 45 678</a>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-25 p-2 rounded-3 me-3 text-white">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                    <a href="mailto:WorkSync@gmail.com" class="text-white-50 fs-6 text-decoration-none hover-white">WorkSync@gmail.com</a>
                                </li>
                            </ul>
                        </div>  
                        <div class="mt-5 pt-4 border-top border-secondary border-opacity-25">
                            <p class="text-white-50 small mb-0"><i class="bi bi-shield-check me-1"></i>We respect your privacy and will not share your information</p>                    
                    </div>
                </div>

            </div>
        </div>
        </div>
    </main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 