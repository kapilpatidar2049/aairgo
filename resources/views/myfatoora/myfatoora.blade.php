<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Myfatoora</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <style>
        body{
            position: relative;
            height: 100vh;
        }
        .center{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        #rzp-button{
            background: #0a8708;
            color: #ffffff;
            padding: 10px;
            font-size:16px;
            border: 1px solid #0a8708;
            border-radius: 10px;
        }
        img{
            margin: auto;
/*            width: 30px;*/
        }
    </style>


</head>
<body>
    @if($payment_data['mode'] == 'test')
        <script src="https://demo.myfatoorah.com/cardview/v1/session.js"></script>

    @elseif ($payment_data['country_code'] == 'SAU')
        <script src="https://sa.myfatoorah.com/cardview/v1/session.js"></script>
    @elseif ($payment_data['country_code'] == 'QAT')
        <script src="https://qa.myfatoorah.com/cardview/v1/session.js"></script>
    @else
        <script src="https://portal.myfatoorah.com/cardview/v1/session.js"></script>
    @endif

    <div class="center">
        <img src="{{ asset('assets/img/myfatoora.png')}}" class="img-fluid">
        <h1>{{ $amount }} {{ $currency }}</h1>
        <div class="card-body">
            <form action="{{ route('myfatoora.checkout.process') }}" method="POST">

                <input type="hidden" name="_token" value="{{csrf_token()}}">
                <input type='hidden' name="amount" value="{{ $amount }}">
                <input type='hidden' name="payment_for" value="{{$payment_for}}">
                <input type='hidden' name="session_id" value="{{$payment_data['session_id']}}">
                <input type='hidden' name="currency" value="{{$currency}}">
                <input type='hidden' name="user_id" value="{{$user_id}}">
                <input type='hidden' name="request_id" value="{{$request_id}}">
                <input type='hidden' name="plan_id" value="{{$plan_id}}">

                @if(count($payment_methods) === 1)
                    <input type="hidden" name="payment_method_id" id="payment_method_id"  value="{{ $payment_methods[0]['PaymentMethodId'] }}">
                @else
                    <select class="form-select mb-3" name="payment_method_id" id="payment_method_id" required>
                        <option disabled value="">Select Payment Method</option>
                        @foreach($payment_methods as $method)
                            <option value="{{ $method['PaymentMethodId'] }}"  data-is-direct="{{ $method['IsDirectPayment'] ? '1' : '0' }}" data-is-card="{{ in_array(strtolower($method['PaymentMethodEn']), ['visa/master', 'visa', 'mastercard', 'mada']) ? '1' : '0' }}">{{ $method['PaymentMethodAr'] }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="w-400px">
                    <div id="card-element"></div>
                </div>

                <div class="alert" style="display: none;">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <span id="error_message"></span>
                </div>
            </form>

                @if($payment_for=="wallet")
                <button class="btn btn-success" id="checkout-live-button">To Wallet</button>
                @else
                <button class="btn btn-success" id="checkout-live-button">Pay Now</button>
                @endif
        </div>
    </div>

<script>
    'use strict';
    const config = {
        countryCode: "{{$payment_data['country_code']}}",
        sessionId: "{{$payment_data['session_id']}}",
        cardViewId: "card-element",
        style: {
            direction: "ltr",
            cardHeight: 180,
            input: {
                color: "black",
                fontSize: "13px",
                fontFamily: "sans-serif",
                inputHeight: "32px",
                inputMargin: "0px",
                borderColor: "c7c7c7",
                borderWidth: "1px",
                borderRadius: "8px",
                boxShadow: "",
                placeHolder: {
                    holderName: "Name On Card",
                    cardNumber: "Number",
                    expiryDate: "MM / YY",
                    securityCode: "CVV",
                }
            },
            label: {
                display: false,
                color: "black",
                fontSize: "13px",
                fontWeight: "normal",
                fontFamily: "sans-serif",
                text: {
                    holderName: "Card Holder Name",
                    cardNumber: "Card Number",
                    expiryDate: "Expiry Date",
                    securityCode: "Security Code",
                },
            },
            error: {
                borderColor: "red",
                borderRadius: "8px",
                boxShadow: "0px",
            },
        },
    };
    myFatoorah.init(config);

    document.getElementById('payment_method_id').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const isCard = selectedOption.dataset.isCard === '1';

        if (isCard) {
            document.getElementById('card-element').innerHTML = '';
            myFatoorah.init(config);
            document.getElementById('card-element').style.display = 'block';
        } else {
            document.getElementById('card-element').style.display = 'none';
        }
    });

    document.getElementById("checkout-live-button").addEventListener("click", async function (e) {
        e.preventDefault();

        const selectedMethodId = document.getElementById("payment_method_id")?.value;

        if (!selectedMethodId) {
            alert("Please select a payment method.");
            return;
        }

        try {
            await myFatoorah.submit()
            .then(function (response) {
                var sessionId = response.SessionId;
                var cardBrand = response.CardBrand;
                let selectedMethodId = document.getElementById("payment_method_id").value;


                var request = new XMLHttpRequest();
                request.open("POST", "{{route('myfatoora.checkout.process')}}");
                request.onreadystatechange = function () {
                    if (this.readyState === 4) {
                        if (this.status === 200) {
                            console.log(JSON.parse(this.responseText));
                            location.href = JSON.parse(this.responseText);
                        } else {
                            console.log(this.response);
                            var error_field = document.getElementById("error_message");
                            var error_message = this.responseText;
                            let finalString = error_message.split('"').join('')
                            error_field.innerText = finalString;
                            error_field.parentElement.style.display = 'block';
                        }

                    }
                };
                var data = new FormData();
                data.append('_token', '{{csrf_token()}}')
                data.append('session_id', sessionId);
                data.append('payment_method_id', selectedMethodId);
                data.append('amount', '{{ $amount }}');
                data.append('payment_for', '{{ $payment_for }}');
                data.append('currency', '{{ $currency }}');
                data.append('user_id', '{{ $user_id }}');
                data.append('request_id', '{{ $request_id }}');
                data.append('plan_id', '{{ $plan_id }}');
                data.append('cardBrand', cardBrand);
                request.send(data);
            });
        } catch (error) {
            const error_field = document.getElementById("error_message");
            error_field.innerText = error.message || error;
            error_field.parentElement.style.display = 'block';
            console.error("MyFatoorah Error:", error);
        }
    });
</script>

</body>
</html>
