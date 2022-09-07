<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>PHP Stripe Checkout and Subscription</title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous">
      <link rel="stylesheet" type="text/css" href="main.css">
      <script type="text/javascript" src="https://js.stripe.com/v3"></script>
      <style type="text/css">
         #card-element{
            width: 100%;
         }
         .card-errors{
            padding: 5px;
            width: 100%;
            margin-top: 10PX;
            margin-bottom: 10px;
            font-size: 12px;
            color: #fff;
            background: red;
         }
      </style>
   </head>
   <body>
<?php
   $products = [
      [
         "title"=>"Product 1",
         "price"=>10,
         "img_url"=>"https://i.imgur.com/QRwjbm5.jpg",
         "qty"=>1
      ],
      [
         "title"=>"Product 2",
         "price"=>25,
         "img_url"=>"https://i.imgur.com/QRwjbm5.jpg",
         "qty"=>1
      ],
      [
         "title"=>"Product 3",
         "price"=>15,
         "img_url"=>"https://i.imgur.com/QRwjbm5.jpg",
         "qty"=>1
      ]
   ];

   $subscriptions = [
      [
         "product"=>[
            "title"=>"Monthly Subscription"
         ],
         "price"=>27,
         "billing_cycle"=>"monthly",
         "subscription_price_id"=>"price_"//your stripe subscription price id
      ]
   ];

   $total = 0;
   $grand_total = 0;
   $shipping = 0;
   $subscription_total = 0;
   $subscriptionProcessingFee = 3.50;//percentage

?>


      <div class="container mt-5 p-3 rounded cart">
         <div class="row no-gutters">
            <div class="col-md-8">
               <div class="product-details mr-2">
                  <div class="d-flex flex-row align-items-center"><i class="fa fa-long-arrow-left"></i><span class="ml-2">Continue Shopping</span></div>
                  <hr>
                  <h6 class="mb-0">Shopping cart</h6>
                  <div class="d-flex justify-content-between">
                     <span>You have <?php echo count($products); ?> products & <?php echo count($subscriptions); ?> subscription items</span>
                     <div class="d-flex flex-row align-items-center">
                        <span class="text-black-50">Sort by:</span>
                        <div class="price ml-2"><span class="mr-1">price</span><i class="fa fa-angle-down"></i></div>
                     </div>
                  </div>

                  <?php for($i = 0; $i < count($products); $i++){ $total += $products[$i]["price"]; $grand_total += $products[$i]["price"]; ?>
                  <div class="d-flex justify-content-between align-items-center mt-3 p-2 items rounded">
                     <div class="d-flex flex-row">
                        <img class="rounded" src="<?php echo $products[$i]['img_url']; ?>" width="40">
                        <div class="ml-2"><span class="font-weight-bold d-block"><?php echo $products[$i]['title']; ?></span><span class="spec">Product</span></div>
                     </div>
                     <div class="d-flex flex-row align-items-center"><span class="d-block"><?php echo $products[$i]['qty']; ?></span><span class="d-block ml-5 font-weight-bold"><?php echo $products[$i]['price']; ?>&#163;</span><i class="fa fa-trash-o ml-3 text-black-50"></i></div>
                  </div>
                  <?php } ?>

                  <?php for($i = 0; $i < count($subscriptions); $i++){ $subscription_total += $subscriptions[$i]["price"]; ?>
                  <div class="d-flex justify-content-between align-items-center mt-3 p-2 items rounded">
                     <div class="d-flex flex-row">
                        <img class="rounded" src="<?php echo $products[$i]['img_url']; ?>" width="40">
                        <div class="ml-2"><span class="font-weight-bold d-block"><?php echo $subscriptions[$i]['product']["title"]; ?></span><span class="spec">Subscription</span></div>
                     </div>
                     <div class="d-flex flex-row align-items-center"><span class="d-block"><?php echo $subscriptions[$i]['billing_cycle']; ?></span><span class="d-block ml-5 font-weight-bold"><?php echo $subscriptions[$i]['price']; ?>&#163;</span><i class="fa fa-trash-o ml-3 text-black-50"></i></div>
                  </div>
                  <?php } ?>
               </div>
            </div>
            <div class="col-md-4">
               <form class="payment-info" id="payment-form" action="./server/process.php" method="POST">
                  <div class="mb-3"><label class="credit-card-label">Name on card</label><input type="text" name="name_on_card" class="form-control credit-inputs" placeholder="Name"></div>
                  <div class="mb-3"><label class="credit-card-label">Phone number</label><input type="text" name="phone" class="form-control credit-inputs" placeholder="Ex: +44 20 0000 1212"></div>
                  <div class="mb-3"><label class="credit-card-label">Email</label><input type="email" name="email" class="form-control credit-inputs" placeholder="Your Email ID"></div>

                  <div class="form-row">
                     <label for="card-element">
                        Credit or Debit Card
                     </label>
                     <div id="card-element"></div>
                     <div id="card-errors" role="alert"></div>
                  </div>
                  
                  <hr class="line">
                  <div class="d-flex justify-content-between information"><span>Products Subtotal</span><span>&#163;<?php echo number_format($total, 2); ?></span></div>
                  <div class="d-flex justify-content-between information"><span>Shipping</span><span>&#163;<?php echo $shipping; ?></span></div>
                  <div class="d-flex justify-content-between information"><span>Products Total</span><span>&#163;<?php echo number_format($total+$shipping, 2); ?></span></div>
                  
                  <?php for($i = 0; $i < count($subscriptions); $i++){ ?>
                     <input type="hidden" name="subscription_price_id" value="<?php echo $subscriptions[$i]["subscription_price_id"]; ?>">
                  <?php } ?>

                  <hr class="line">
                  <div class="d-flex justify-content-between information"><span>Subscriptions Total</span><span>&#163;<?php echo number_format($subscription_total, 2); ?></span></div>

                  <hr class="line">
                  <div class="d-flex justify-content-between information"><span>Grand Total</span><span>
                     &#163;<?php echo number_format(($grand_total + $shipping + $subscription_total), 2); ?>
                  </span></div>

                  <input type="hidden" name="products" value="<?php echo json_encode($products); ?>">
                  <input type="hidden" name="subscriptions" value="<?php echo json_encode($subscriptions); ?>">
                  <input type="hidden" name="subscription_total" value="<?php echo $subscription_total; ?>">

                  <button id="submitBtn" class="btn btn-primary btn-block d-flex justify-content-between mt-3" type="submit"><span>&#163;<?php echo number_format(($grand_total + $shipping + $subscription_total ), 2); ?></span><span>Pay Now<i class="fa fa-long-arrow-right ml-1"></i></span></button>
               </form>
            </div>
         </div>
      </div>
      <div id="form__processing__gif">
         <div class="gif_wrapper_">
            <img src="./assets/images/processing.gif">
            <p style="text-align: center;">Please wait...</p>
         </div>
      </div>

      <!-- Modal -->
      <div class="modal fade" id="paymentStatusModal" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header" style="background: #ddd;">
              <h5 class="modal-title" id="exampleModalLabel"></h5>
              <button onclick="reloadWebPage()" type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
               <h3 class="text-center heading_">Your Payment and Subscription Status</h3>
               <div class="row">
                  <div class="col-lg-12 col-md-12 col-sm-12">

                     <div class="table-responsive" id="dynamicPaymentHTML">
                        
                     </div>
                  </div>
               </div>
            </div>
          </div>
        </div>
      </div>

      <!-- scirpts -->
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.min.js" integrity="sha384-VHvPCCyXqtD5DqJeNxl2dtTyhF78xXNXdkwX1CZeRusQfRKp+tA7hAShOK/B/fQ2" crossorigin="anonymous"></script>
      <script type="text/javascript" src="./assets/js/sweetalert2@10.js"></script>



<?php
  
// Store the cipher method
$ciphering = "AES-128-CTR";
  
// Use OpenSSl Encryption method
$iv_length = openssl_cipher_iv_length($ciphering);
$options = 0;
  
// Non-NULL Initialization Vector for encryption
$encryption_iv = '1234567891011121';
  
// Store the encryption key
$encryption_key = "afalkfjdlskafjjfalsdkfjklsdaf!865689-fjadklfjdf-0=fjasdfjd";
  
// Use openssl_encrypt() function to encrypt the data
$encryptedGrandTotal = openssl_encrypt($grand_total, $ciphering,
            $encryption_key, $options, $encryption_iv); 
?>

      <script type="text/javascript">
         const myStyles = {
            base:{
               iconColor:"rgb(255, 255, 255)",
               color:"rgb(255, 255, 255)",
               "::placeholder":{color:"rgb(255, 255, 255)"}
            }
         }

         const stripe = Stripe("pk_test_")//publishable key
         const elements = stripe.elements()
         const card = elements.create("card", {style:myStyles})
         card.mount("#card-element")
         const cardErrorElement = document.getElementById("card-errors")



         //handle submit
         const form = document.getElementById("payment-form")
         const animation = document.getElementById("form__processing__gif")
         form.addEventListener("submit", function(e){
            e.preventDefault()

            $(animation).show()
            //validate all inputs
            const nameOnCard = $("input[name='name_on_card']")
            const phone = $("input[name='phone']")
            const email = $("input[name='email']")
            const grandTotal = "<?php echo $encryptedGrandTotal; ?>" //products grand total + subscription fee
            const subscriptionPriceId = $("input[name='subscription_price_id']")
            const products = $("input[name='products']")

            if (!nameOnCard || nameOnCard.val() == "") {
               fireError("Name on card is required")
               return
            }
            if (!phone || phone.val() == "") {
               fireError("Phone number is required")
               return
            }
            if (!email || email.val() == "") {
               fireError("Email is required")
               return
            }
            if (!validateEmail(email.val())) {
               fireError("Invalid email, please enter valid email address.")
               return
            }

            if (!products || products.val() == '') {
               fireError("Invalid request, no products found, please refresh the page and try again.")
               return
            }

            if (!subscriptionPriceId || subscriptionPriceId.val() == '') {
               fireError("Invalid request, subscription price id not found")
               return
            }

            //now create token
            console.log("card", card)
            stripe.createToken(card)
            .then(function(result){
               if (result.error) {
                  fireError(result.error.message)
               }else{
                  //create payment method
                  stripe.createPaymentMethod({
                     type:"card",
                     card:card,
                     billing_details:{
                        name:nameOnCard.val()
                     }
                  }).then((res)=>{
                     console.log("createPaymentMethod", res)
                     console.log("id", res.paymentMethod.id)

                     if (res.error) {
                        console.log("Creating payment method failed: ", res.error)
                     }else{
                        const data = {
                           "stripeToken":result.token.id,
                           "nameOnCard":nameOnCard.val(),
                           "phone":phone.val(),
                           "email":email.val(),
                           "grandTotal":grandTotal,
                           "subscriptionPriceId":subscriptionPriceId.val(),
                           "paymentMethodId":res.paymentMethod.id,
                           "products":"dummy"//products.val()
                        }
                        console.log(data)
                        processThePayment(data)
                     }
                  })

               }
            })
         })

         //email validator
         const validateEmail = (email) => {
           return String(email)
             .toLowerCase()
             .match(
               /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
             );
         };


         function processThePayment(data){
            //console.log(data)
            $.ajax({
               url:"./server/process.php",
               method:"POST",
               data:data,
               dataType:"JSON",
               success:function(response){
                  console.log(response)
                  if (response.msg === "required_3d_secure") {
                     stripe.handleCardAction(response.client_secret).then((result_)=>{
                        console.log("cardActionResult:", result_)
                        if(result_.hasOwnProperty("error")){
                           alert(result_.error.message)
                           return window.location.reload(true)
                        }
                        confirmThePaymentIntent(result_, data, response.customer)
                     })
                  }else{
                     confirmThePaymentNow(response)
                  }
                  
               },
               error:function(err){
                  console.log(err)
                  fireError("An error occured | "+err.responseText)
               }
            })
         }

         function confirmThePaymentIntent(result, data, customer){
            data['payment_intent_id'] = result.paymentIntent.id
            data['customer_id'] = customer.id

            console.log("confirmingPaymentItentId:", data)
            $.ajax({
               url:"./server/process.php",
               method:"POST",
               data:data,
               dataType:"JSON",
               success:function(response){
                  console.log("paymentIntentConfirmationResponse:", response)
                  response['customer'] = customer
                  showModalHTML(
                     response.paymentIntent.status, 
                     response.paymentIntent.amount,
                     response
                  )
                  
               },
               error:function(err){
                  console.log(err)
                  fireError("An error occured | "+err.responseText)
               }
            })
         }


         function confirmThePaymentNow(response){
            //process the checkout payment confirmation
            // stripe.confirmCardPayment(response.checkout_client_secret, {
            //    payment_method:{card:card}
            // })
            // .then(transactionStatus =>{
            //    console.log(transactionStatus)

            //    // if (transactionStatus.paymentIntent.status && transactionStatus.paymentIntent.status === "succeeded"){
                  
            //    // }
            //    showModalHTML(
            //       transactionStatus.paymentIntent.status, 
            //       transactionStatus.paymentIntent.amount,
            //       response
            //    )

            // })

            showModalHTML(
               response.paymentIntent.status, 
               response.paymentIntent.amount,
               response
            )
         }


         function showModalHTML(status, amount, response){
            const html_ = `
               <p class="m-0">Payment Status : <span class="badge badge-success">${status}</span></p>
               <p class="m-0">Subscription Status : <span class="badge badge-success">${response.subscription.status}</span></p>
               <p class="m-0">Paid Amount : <span>&#163;${(parseInt(amount) / 100).toFixed(2)}</span></p>
               <p class="m-0">Subscription Amount : <span>${(parseInt(response.subscription.plan.amount) / 100).toFixed(2)} ${response.subscription.plan.currency.toUpperCase()}</span></p>
               ${response.hasOwnProperty('hosted_invoice_url') ? `<p style='font-size: 16px; font-weight: bold; text-align: center; padding: 10px; border: 2px solid yellow; margin-top: 10px;'>
                  Payment Collection was succesfull but for the subscription we need your additional actions due to 3D SCA Card, please pay the subscription fee on the Stripe.com hosted url.
                  <br> We will redirect you in 8 seconds,
                  <br>please wait....!
               </p>` : ``}

               <table class="table table-hover">
                  <tr>
                     <th>Your Customer Id</th>
                     <td width="5%">:</td>
                     <td>${response.customer.id}</td>
                  </tr>
                  <tr>
                     <th>Name</th>
                     <td width="5%">:</td>
                     <td>${response.customer.name}</td>
                  </tr>
                  <tr>
                     <th>Email</th>
                     <td width="5%">:</td>
                     <td>${response.customer.email}</td>
                  </tr>
                  <tr>
                     <th>Phone</th>
                     <td width="5%">:</td>
                     <td>${response.customer.phone}</td>
                  </tr>
                  <tr>
                     <th>Your Subscription ID</th>
                     <td width="5%">:</td>
                     <td>${response.subscription.id}</td>
                  </tr>
                  <tr>
                     <th>Subscription Status</th>
                     <td width="5%">:</td>
                     <td>${response.subscription.status}</td>
                  </tr>
                  <tr>
                     <th>Subscription Billing</th>
                     <td width="5%">:</td>
                     <td>${response.subscription.billing}</td>
                  </tr>
               </table>`
               $("#dynamicPaymentHTML").html(html_)

               $(animation).hide()
               $("#paymentStatusModal").modal("show")

               if (response.hasOwnProperty('hosted_invoice_url')) {
                  setTimeout(function(){
                     window.location.href = response.hosted_invoice_url
                  }, 8000)
               }
               
         }


         function fireError(msg){
            $(animation).hide()
            Swal.fire({
              icon: 'info',
              title: 'Sorry',
              text: msg,
            })
         }

         function reloadWebPage(){
            window.location.reload(true)
         }
      </script>

   </body>
</html>