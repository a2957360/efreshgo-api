<?php  require("config.php"); ?>
 
<html>
    
    <head>
        <link rel="stylesheet" href="./bootstrap-4.3.1-dist/css/bootstrap.min.css">
        <style>
            .container{
                margin-top: 100px;
                
            }
            .card{
                width: 300px
            }
            .card:hover{
                  
            }
        
        </style>
    </head>
    <body>
        <div class="container">
        <?php 
            $colNum = 1;
            
            foreach($products as $productID => $attributes){
                if($colNum ==1){
                    echo "<div class='row'>";
                }
                echo ' <div class="col-md-4"> 
                <div class="card">
                    <div class="card-header">
                         '. $attributes['price'] .'
                    </div>
                    <div class="card-body">
                        <div class="card-title">
                           '. $attributes['title'].'
                        </div>
                        <ul class="list-group">
                         ';
               foreach($attributes['features'] as $feature)   
                        echo " <li class='list-group-item'>". $feature ."</li>";
             
                    echo '    </ul>
                    <br>
                    <form action="stripeIPN.php?id='.$productID .'" method="post">
                        <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                        data-key="'.$stripeDetail['publishableKey'].'"
                        data-amount="'.$attributes['price'].'"
                        data-name="'.$attributes['title'].'"
                        data-description="Widget"
                        data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
                        data-locale="auto">
                        </script>
                        </form>
                    </div>
                </div>
                </div>';
                
                if($colNum ==3){
                    echo "</div>";
                    $colNum = 0;
                }else{
                    $colNum++;
                }
            }
            ?> 
              
            
            
            </div> 
    </body>
</html>