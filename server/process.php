<?php
   if (!isset($_POST["stripeToken"])) {
      header("Content-Type:application/json");
      header("HTTP/1.1 403 Forbidden");
      echo json_encode([
         "success"=>false,
         "msg"=>"Invalid request, HTTP POST only allowed!"
      ]);
      return;
   }

   //validate form data
   $stripeToken = $_POST['stripeToken'];
   $name_on_card = $_POST['nameOnCard'];
   $grand_total = $_POST['grandTotal'];
   $products = $_POST['products'];
   $phone = $_POST['phone'];
   $email = $_POST['email'];
   $subscription_price_id = $_POST['subscriptionPriceId'];
   $paymentMethodId = $_POST['paymentMethodId'];
   $paymentIntentId = isset($_POST['payment_intent_id']) && $_POST['payment_intent_id'] != "" ? $_POST['payment_intent_id'] : NULL;

   if($stripeToken == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Invalid request, Stripe token was missing! please try again later."
      ]);
      return;
   }

   if($name_on_card == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Name on card is required"
      ]);
      return;
   }
   if($phone == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Phone is required"
      ]);
      return;
   }
   if($email == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Email is required"
      ]);
      return;
   }
   if($grand_total == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Grand total amount is required"
      ]);
      return;
   }
   if($subscription_price_id == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Subscription price id is required"
      ]);
      return;
   }

   if($products == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Products data is required"
      ]);
      return;
   }
   if($paymentMethodId == ""){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Payment method id is required"
      ]);
      return;
   }

   //decrypt grand total amount
   $ciphering = "AES-128-CTR";
     
   // Use OpenSSl Encryption method
   $iv_length = openssl_cipher_iv_length($ciphering);
   $options = 0;
          
   // Store the encryption key
   $encryption_key = "afalkfjdlskafjjfalsdkfjklsdaf!865689-fjadklfjdf-0=fjasdfjd";
     
   // Non-NULL Initialization Vector for decryption
   $decryption_iv = '1234567891011121';

   // Use openssl_decrypt() function to decrypt the data
   $grand_total = openssl_decrypt ($grand_total, $ciphering, 
           $encryption_key, $options, $decryption_iv);

   if (!is_numeric($grand_total) && !$grand_total > 0) {
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"The grand total amount is invalid, please refresh the page and try again"
      ]);
      return;
   }

   //Load Stripe
   require_once('../vendor/autoload.php');
   $stripe = new \Stripe\StripeClient('sk_test_'); //secret key
   $connected_acc_id = "acct_";//connected account id if you want to forward money to another account
   $subscriptionProcessingFee = 3.50;//percentage

   //if has intent-- so confirm it and done
   if ($paymentIntentId) {
      //\Stripe\Stripe::
      //$intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
      $intent = $stripe->paymentIntents->retrieve($paymentIntentId);
      $intent->confirm();
      //echo json_encode($intent);
      if(!isset($intent['status']) || $intent['status'] !== "succeeded"){
         header("Content-Type:application/json");
         header("HTTP/1.1 400 Bad Request");
         echo json_encode([
            "success"=>false,
            "msg"=>"Payment Intent Confirmation Failed"
         ]);
         return;
      }

      //subscribe...
      $subscription = $stripe->subscriptions->create([
         'customer' => $_POST['customer_id'],
         "payment_behavior"=>"default_incomplete",
         'items' => [
           ['price' => $subscription_price_id],
         ],
         // "expand" => ["latest_invoice.payment_intent"],
         "application_fee_percent"=>$subscriptionProcessingFee,
         "transfer_data" => [
           "destination" => $connected_acc_id
         ]
         //"payment_method"=>$paymentMethodId,
       ]);

      //get latet invoice
      if (!isset($subscription["id"]) || $subscription["id"] == '' || $subscription["status"] !== "active") {
         //get latest invoice
         $invoice = $stripe->invoices->retrieve(
           $subscription['latest_invoice'],
           []
         );

         if (isset($invoice['status']) && $invoice['status'] === 'open' && isset($invoice['hosted_invoice_url'])) {
            echo json_encode([
               "success"=>true,
               "checkout_client_secret"=>$intent['client_secret'],
               //"customer"=>$customer,
               "subscription"=>$subscription,
               "paymentIntent"=>$intent,
               "hosted_invoice_url"=>$invoice['hosted_invoice_url'],
               "invoice"=>$invoice
            ]);
            return;
         }

         
         header("Content-Type:application/json");
         header("HTTP/1.1 400 Bad Request");
         echo json_encode([
            "success"=>false,
            "checkout_client_secret"=>$intent['client_secret'],
            //"customer"=>$customer,
            "subscription"=>$subscription,
            "paymentIntent"=>$intent,
            "invoice"=>$invoice,
            "message"=>"Fetching latest invoice was failed!"
         ]);
         return;
         
      }


      header("Content-Type:application/json");
      header("HTTP/1.1 200 OK");
      echo json_encode([
         "success"=>true,
         "checkout_client_secret"=>$intent['client_secret'],
         //"customer"=>$customer,
         "subscription"=>$subscription,
         "paymentIntent"=>$intent
      ]);
      return;
      
      
   }

   //First create a customer
   $customer = $stripe->customers->create([
      'name'=>$name_on_card,
      "phone"=>$phone,
      'description' =>"Customer created during checkout and subscription.",
      'email' => $email,
      "source" => $stripeToken
   ]);

   if(!isset($customer["id"]) || $customer["id"] == ''){
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Creating customer on Stripe has been failed, please try again later."
      ]);
      return;
   }
   //attach payment method
   $stripe->paymentMethods->attach(
      $paymentMethodId,
      ['customer' => $customer["id"]]
    );

   //  $stripe->setupIntents->create(
   //    [
   //      'customer' => $customer["id"],
   //      'payment_method_types' => ['card'],
   //    ]
   //  );

   //now create checkout intent
   $paymentIntentsCheckout = $stripe->paymentIntents->create([
      "amount"=>$grand_total * 100,//convert to cents
      "currency"=>"gbp",
      "payment_method_types"=>["card"],
      "description"=>"Collected payments on behalf of MyApp.com",
      "confirmation_method"=>"manual",
      "confirm"=>true,
      "payment_method"=>$paymentMethodId,
      'customer' => $customer->id,
      'setup_future_usage' => 'off_session',
      // 'automatic_payment_methods' => [
      //    'enabled' => 'true',
      //  ],
   ]);
   //echo json_encode($paymentIntentsCheckout);
   //return;
   if (!isset($paymentIntentsCheckout['status'])) {
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Creating payment intent has been failed"
      ]);
      return;
   }

   if ($paymentIntentsCheckout['status'] === "requires_source_action" && $paymentIntentsCheckout['next_source_action']['type'] === "use_stripe_sdk") {
      header("Content-Type:application/json");
      header("HTTP/1.1 200 OK");
      echo json_encode([
         "success"=>true,
         "msg"=>"required_3d_secure",
         "client_secret"=>$paymentIntentsCheckout['client_secret'],
         "customer"=>$customer,
         "paymentIntent"=>$paymentIntentsCheckout
      ]);
      return;
   }

   //now create subscription first
   $subscription = $stripe->subscriptions->create([
     'customer' => $customer->id,
     'items' => [
       ['price' => $subscription_price_id],
     ],
     "expand" => ["latest_invoice.payment_intent"],
     "application_fee_percent"=>$subscriptionProcessingFee,
     "transfer_data" => [
       "destination" => $connected_acc_id
     ]
   ]);

   if (!isset($subscription["id"]) || $subscription["id"] == '' || $subscription["status"] !== "active") {
      header("Content-Type:application/json");
      header("HTTP/1.1 400 Bad Request");
      echo json_encode([
         "success"=>false,
         "msg"=>"Creating subscription on Stripe has been failed, please try again later."
      ]);
      return;
   }



   //send back the checkout token
   header("Content-Type:application/json");
   header("HTTP/1.1 200 OK");
   echo json_encode([
      "paymentIntent"=>$paymentIntentsCheckout,
      "checkout_client_secret"=>$paymentIntentsCheckout->client_secret,
      "customer"=>$customer,
      "subscription"=>$subscription
   ]);
   return;
