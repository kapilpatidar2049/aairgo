<!DOCTYPE html>
<html>
<head>
    <title>Pay with PIX</title>
</head>
<body style="text-align: center; font-family: sans-serif;margin-top:100px;">
    <h2>Scan the PIX QR Code</h2>
    <div style="display:grid;place-items:center;">
    <span class="badge yellow">After completing your payment, you may safely close this tab.</span>
    </div>
    <img src="{{ $payment['qrCodeImage'] }}" alt="PIX QR Code" style="width: 300px;"><br><br>
    <p>Or copy the PIX code below:</p>
    <textarea readonly style="width: 90%; height: 100px;">{{ $payment['brCode'] }}</textarea>
    <p><strong>Amount:</strong> {{ number_format($amount, 2) }} {{ $currency }}</p>

    <p>Waiting for payment confirmation...</p>
    

    <!-- Optional auto polling to check payment status -->
</body>
<style>
  .badge {
    display: block;
    padding: 8px 12px;
    font-size: 18px;
    font-weight: 600;
    color: white;
    background-color: #4caf50; /* Green */
    border-radius: 20px;
    text-align: center;
    width:600px;
  }
  .badge.yellow {
    background-color: #ffc107; /* Yellow */
    color: black;
  }
</style>
</html>
