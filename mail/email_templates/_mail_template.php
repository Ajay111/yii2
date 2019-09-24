<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Algn</title>
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
        <!-- Bootstrap -->

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
              <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
              <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
    </head>
    <body>
        <style>
            *{
                font-family: 'Open Sans', sans-serif;	
                padding:0;
                margin:0;	
            }
        </style>
        <table class="table" style="width:700px;max-width:100%; margin:0 auto;" border="0" cellpadding="0" cellspacing="0" bgcolor="#24a158">
            <tbody>
                <tr>
                    <td align="center"><img src="<?=\Yii::$app->params['base_url']?>/images/logo.png" alt=""/></td>
                </tr>
                <tr>
                    <td align="center">
                        <table class="table" cellpadding="10" cellspacing="10" border="0" bgcolor="#ffffff" width="95%">
                            <tbody>
                                <tr>
                                    <td style="font-size:14px;color:#5b6a6f;padding:10px 25px;line-height: 24px;">

                                        <?= $content ?>

                                    </td>
                                 
                                </tr>


                                <tr>
                                    <td style="font-size:14px;color:#5b6a6f;padding:10px 25px;line-height:24px" colspan="2">
                                        <span style="color:#24a158">See you soon on Algn.!</span><br>
                                        <i>If you don't have the app, Please request your Algn Administrator.</i><br>
                                        Thanks,<br>
                                        Algn Team
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" align="center" valign="top" bgcolor="#f8f8f8" style="font-size:11px;padding:20px 0;line-height:15px;font-weight:500">
                                        You are receiving this email because you have been registered with <span style="color:#24a158">Algn</span>.<br>
                                        @ 2017 PBOPlus Consulting Services Ltd. All rights reserved
                                    </td>
                                </tr>
                            </tbody>
                        </table>        
                    </td>
                </tr>
                <tr>
                    <td><p>&nbsp;</p></td>
                </tr>

            </tbody>

        </table>

    </body>
</html>
