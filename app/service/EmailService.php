<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/2/28
 * Time: 21:50
 */
require APP_PATH . "library/Mailer.php";

class EmailService extends Service
{
    public function sendMail($template, $context, $to, $title)
    {
        if (Config::check("email")) {
            $config = Config::load("email");
            $mailer = new Mailer($config->get('host'), $config->get('port'), true, $config->get('username'), $config->get('password'));
            $body = Template::process($template, $context);
            $mailer->sendMail($to, $config->get('email'), $title, $body, "HTML", TP_SITE_NAME);
        }
    }
}