<?php
session_start();

$plans = [
    'basic'      => ['name' => 'Basic plan',      'price' => 9.99,  'desc' => 'Up to 10 users'],
    'pro'        => ['name' => 'Pro plan',        'price' => 19.99, 'desc' => 'Up to 50 users'],
    'enterprise' => ['name' => 'Enterprise plan', 'price' => 49.99, 'desc' => 'Up to 200 users'],
];

$planKey = $_POST['plan'] ?? $_GET['plan'] ?? $_SESSION['plan'] ?? 'pro';
if (!isset($plans[$planKey])) {
    $planKey = 'pro';
}
$plan = $plans[$planKey];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Kortnummer, utløpsdato og CVC har ikke name-attributt, så de sendes aldri til serveren.
    $cardName = trim($_POST['cardName'] ?? '');

    if ($cardName === '') {
        $error = "Please enter the name on the card";
    } else {
        $_SESSION['paid'] = true;
        $_SESSION['plan'] = $planKey;
        header("Location: register.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - WorkSync</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; background: #f4f6fb; min-height: 100vh; }
  .container { display: flex; min-height: 100vh; }

  .summary {
    width: 40%;
    background: linear-gradient(160deg, #3b7bff 0%, #2f6df6 45%, #1e4fd1 100%);
    color: white;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }
  .summary::after {
    content: "";
    position: absolute;
    width: 420px; height: 420px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    top: -140px; right: -140px;
  }
  .summary > * { position: relative; }
  .summary h1 { font-family: 'Poppins', sans-serif; font-size: 40px; font-weight: 700; margin-bottom: 8px; }
  .summary .tag { font-size: 15px; letter-spacing: 2px; color: rgba(255,255,255,0.85); margin-bottom: 50px; }
  .order {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 14px;
    padding: 24px;
    backdrop-filter: blur(4px);
  }
  .order .label { font-size: 13px; color: rgba(255,255,255,0.8); margin-bottom: 6px; }
  .order .plan-name { font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 600; }
  .order .plan-desc { font-size: 14px; color: rgba(255,255,255,0.85); margin-bottom: 20px; }
  .order .total {
    display: flex; justify-content: space-between; align-items: baseline;
    border-top: 1px solid rgba(255,255,255,0.3);
    padding-top: 16px;
  }
  .order .total .amount { font-family: 'Poppins', sans-serif; font-size: 26px; font-weight: 700; }
  .order .total .per { font-size: 13px; color: rgba(255,255,255,0.85); }

  .form-side { width: 60%; background: white; padding: 60px 90px; display: flex; flex-direction: column; justify-content: center; }
  .form-side h2 { font-family: 'Poppins', sans-serif; font-size: 28px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
  .form-side .sub { color: #7a7f8c; font-size: 15px; margin-bottom: 28px; }
  label { display: block; font-size: 13px; font-weight: 600; color: #4a4f5c; margin-bottom: 6px; }
  input[type="text"] {
    width: 100%; height: 46px;
    border: 1.5px solid #e1e4ec; background: #f9fafc;
    border-radius: 10px; padding: 0 15px;
    font-size: 15px; font-family: 'Inter', sans-serif;
    margin-bottom: 18px;
    transition: border-color .2s, box-shadow .2s, background-color .2s;
  }
  input:focus { outline: none; border-color: #2f6df6; background: #fff; box-shadow: 0 0 0 4px rgba(47,109,246,0.12); }
  .row { display: flex; gap: 16px; }
  .row > div { flex: 1; }

  button {
    width: 100%; margin-top: 6px;
    background: linear-gradient(135deg, #3b7bff, #2f6df6);
    border: none; color: white;
    padding: 14px 25px; font-size: 15px; font-weight: 600;
    border-radius: 10px; cursor: pointer;
    box-shadow: 0 6px 16px rgba(47,109,246,0.28);
    transition: transform .15s, box-shadow .15s, opacity .15s;
  }
  button:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(47,109,246,0.35); }
  button:disabled { opacity: .75; cursor: default; transform: none; }
  .spinner {
    display: none; width: 16px; height: 16px; margin-right: 8px; vertical-align: -3px;
    border: 2px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .error {
    background: #fff0f0; border: 1px solid #f3c2c2; color: #c0392b;
    padding: 12px 14px; margin-bottom: 20px; border-radius: 8px; font-size: 14px; display: none;
  }
  .error.show { display: block; }
  .secure { text-align: center; color: #9aa0ae; font-size: 12.5px; margin-top: 16px; }

  .done { text-align: center; }
  .done .check {
    width: 72px; height: 72px; border-radius: 50%;
    background: #e5ffe9; color: #0a8a2b;
    font-size: 36px; line-height: 72px; margin: 0 auto 22px;
  }
  .done a {
    display: inline-block; margin-top: 26px; text-decoration: none;
    background: linear-gradient(135deg, #3b7bff, #2f6df6); color: white;
    padding: 13px 30px; border-radius: 10px; font-weight: 600; font-size: 15px;
  }

  @media (max-width: 900px) {
    .container { flex-direction: column; }
    .summary, .form-side { width: 100%; padding: 32px; }
  }
</style>
</head>
<body>
<div class="container">
  <div class="summary">
    <h1>WORKSYNC</h1>
    <div class="tag">A SMARTER DAY AT THE OFFICE</div>
    <div class="order">
      <div class="label">Your plan</div>
      <div class="plan-name"><?= htmlspecialchars($plan['name']) ?></div>
      <div class="plan-desc"><?= htmlspecialchars($plan['desc']) ?></div>
      <div class="total">
        <span class="amount">$<?= number_format($plan['price'], 2) ?></span>
        <span class="per">per month</span>
      </div>
    </div>
  </div>

  <div class="form-side">
      <h2>Payment details</h2>
      <p class="sub">Complete your subscription to get started.</p>

      <div class="error <?= $error ? 'show' : '' ?>" id="errorBox"><?= htmlspecialchars($error) ?></div>

      <form method="POST" id="payForm" novalidate>
        <input type="hidden" name="plan" value="<?= htmlspecialchars($planKey) ?>">

        <label for="cardName">NAME ON CARD</label>
        <input type="text" id="cardName" name="cardName" placeholder="Full name" autocomplete="cc-name">

        <!-- Ingen name-attributt: disse feltene sendes aldri til serveren -->
        <label for="cardNumber">CARD NUMBER</label>
        <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" inputmode="numeric" maxlength="19" autocomplete="cc-number">

        <div class="row">
          <div>
            <label for="expiry">EXPIRY</label>
            <input type="text" id="expiry" placeholder="MM/YY" inputmode="numeric" maxlength="5" autocomplete="cc-exp">
          </div>
          <div>
            <label for="cvc">CVC</label>
            <input type="text" id="cvc" placeholder="123" inputmode="numeric" maxlength="3" autocomplete="cc-csc">
          </div>
        </div>

        <button type="submit" id="payBtn"><span class="spinner" id="spinner"></span><span id="btnText">Pay $<?= number_format($plan['price'], 2) ?></span></button>
      </form>
      <div class="secure">🔒 Secure payment. This is a demo, no real payment is made.</div>
  </div>
</div>

<script>
(function () {
  var form = document.getElementById('payForm');
  if (!form) return;

  var num = document.getElementById('cardNumber');
  var exp = document.getElementById('expiry');
  var cvc = document.getElementById('cvc');
  var box = document.getElementById('errorBox');

  num.addEventListener('input', function () {
    var d = num.value.replace(/\D/g, '').slice(0, 16);
    num.value = d.replace(/(.{4})/g, '$1 ').trim();
  });
  exp.addEventListener('input', function () {
    var d = exp.value.replace(/\D/g, '').slice(0, 4);
    exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
  });
  cvc.addEventListener('input', function () {
    cvc.value = cvc.value.replace(/\D/g, '').slice(0, 3);
  });

  function fail(msg) {
    box.textContent = msg;
    box.classList.add('show');
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    box.classList.remove('show');

    if (!document.getElementById('cardName').value.trim()) return fail('Please enter the name on the card');
    if (num.value.replace(/\s/g, '').length !== 16) return fail('Card number must be 16 digits');

    var m = exp.value.match(/^(\d{2})\/(\d{2})$/);
    if (!m || +m[1] < 1 || +m[1] > 12) return fail('Enter expiry as MM/YY');
    var now = new Date();
    var year = 2000 + (+m[2]);
    if (year < now.getFullYear() || (year === now.getFullYear() && +m[1] < now.getMonth() + 1)) {
      return fail('This card has expired');
    }
    if (cvc.value.length !== 3) return fail('CVC must be 3 digits');

    // Simulert behandling
    document.getElementById('payBtn').disabled = true;
    document.getElementById('spinner').style.display = 'inline-block';
    document.getElementById('btnText').textContent = 'Processing...';
    setTimeout(function () { form.submit(); }, 1800);
  });
})();
</script>
</body>
</html>