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
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>

    :root{
        --brand: #3b71ca;
        --brand-dark: #2b4fc4;

    }
    *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family:Arial, Helvetica, sans-serif;
    }
    body{
     background: linear-gradient(135deg, #f4f7fc, #dce6f8 100%);
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

.contact-card{
    border: none;
    box-shadow: 0 20px 50px rgba(43, 79, 196, 0.15);
}

.bg-custom-dark{
    background-color: var(--brand);
    color: #fff;
}


.contact-link:hover{
    color: #fff !important;
}

.form-control:focus{
    border-color: var(--brand);
    box-shadow: 0 0 0 0.2rem rgba(59, 113, 202, 0.2);
}

.btn-custom{
    background-color: var(--brand);
    border-color: var(--brand);
    color: #fff;
}

.btn-custom:hover,
.btn-custom:focus{
    background-color: var(--brand-dark);
    border-color: var(--brand-dark);
    color: #fff;
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
                        <h1 class="fw-bold mb-2" style="font-size: 2.5rem;">Get in touch </h1>
                        <p class="text-secondary fs-5">Our team is always here to help you.</p>
                    </div>
                        <div class="card contact-card rounded-4 overflow-hidden bg-white">
                            <div class="row g-0">

                                <div class="col-lg-7 p-4 p-md-5">
                                 <form action="contact.php" method="POST">

                                <div class="mb-3">
                                    <label for="nameInput" class="form-label text-secondary small fw-semibold text-uppercase">Name</label>
                                    <input type="text" id="nameInput" name="name" class="form-control rounded-3" placeholder="Your name" autocomplete="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="emailInput" class="form-label text-secondary small fw-semibold text-uppercase">Email</label>
                                    <input type="email" id="emailInput" name="email" class="form-control rounded-3" placeholder="Your email" autocomplete="email" required>
                                </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="phoneInput" class="form-label text-secondary small fw-semibold text-uppercase">Phone</label>
                                    <input type="tel" id="phoneInput" name="phone" class="form-control rounded-3" placeholder="Phone number" autocomplete="tel" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="companyInput" class="form-label text-secondary small fw-semibold text-uppercase">Company</label>
                                    <input type="text" id="companyInput" name="company" class="form-control rounded-3" placeholder="Company name" autocomplete="organization" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="messageInput" class="form-label text-secondary small fw-semibold text-uppercase">Message</label>
                                <textarea name="message" id="messageInput" class="form-control rounded-3" placeholder="Tell us what we can help you with..." rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-custom w-100 py-3 rounded-3 fw-semibold">Send Message</button>
                        </form>
                    </div>

                    <div class="col-lg-5 p-4 p-md-5 bg-custom-dark text-white d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="fw-bold mb-3">Contact Information</h4>
                            <p class="text-white-50 mb-5 fs-6 " style="line-height: 1.6;">Feel free to reach out to us using the contact information below or send us a message. We are here to assist you.</p>
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
                                    <a href="tel:+4712345678" class="contact-link text-white-50 fs-6 text-decoration-none">+47 123 45 678</a>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-25 p-2 rounded-3 me-3 text-white">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                    <a href="mailto:WorkSync@gmail.com" class="contact-link text-white-50 fs-6 text-decoration-none">WorkSync@gmail.com</a>
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
    </div>
    </main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 