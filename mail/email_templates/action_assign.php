<?php
//echo $this->render('./_mail_header');
?>


<?php
$content = '
<p>Hello vfvfv,</p>
<p>You has been assigned for a action <b><?php echo "action_name"; ?></b> scheduled to complete at <?php echo $data ?>.</p>
<!-- <p><strong>Would you like to do Accept Decline?</strong></p>-->'; 

echo $this->render('./_mail_template',['content'=>$content]);


?>


<?php
//echo $this->render('./_mail_footer');
?>             