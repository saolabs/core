<?php
namespace Saola\Core\Engines;

use Saola\Core\Mailer\Email;

Class SystemMailAlert{
    # code here...
    /**
     * gửi email thong báo
     *
     * @param string|array $subject
     * @param string $content
     * @return void
     */
    public static function send($subject = null, $content = null)
    {
        if(is_array($subject)){
            $a = $subject;
            if(isset($a['content'])){
                $content = $a['content'];
            }
            if(isset($a['subject'])){
                $subject = $a['subject'];
            }else{
                $subject = "Thông báo";
            }
        }
        $_email = env('MAIL_ALERT_ADDRESS', env('MAIL_USERNAME'));

        if($_email){
            $to = [];
            if(count($lines = nl2array($_email))){
                foreach ($lines as $line) {
                    $emails = array_filter(array_map('trim', explode(',', $_email)), function($email){
                        return strlen($email) > 0 && filter_var($email, FILTER_VALIDATE_EMAIL);
                    });
                    foreach ($emails as $email) {
                        $to[] = $email;
                    }
                }
            }
            
            if($to){
                $mailFrom = 'support@fpoly.vn';
                $company = 'VCC Corp';
                Email::from($mailFrom, $company)
                    ->to($to)
                    ->subject($subject)
                    ->body('mails.simple-alert')
                    ->data(['content' => $content])
                    ->sendAfter(1);
            }
        }
            
    }
}