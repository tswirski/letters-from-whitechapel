<?php
/**
 * Created by PhpStorm.
 * User: lama
 * Date: 2016-07-18
 * Time: 00:13
 */

class SwiftMessage extends Swift_Message {
    protected function getTransport(){
        return Swift_SmtpTransport::newInstance('smtp.mailgun.org', 465, 'ssl')
            ->setUsername('no-replay@fantasybyte.com')
            ->setPassword('ae6bc7d9c05c412a36');
    }

    public static function create() {
        $instance = new self();
        $instance->setFrom(['no-replay@fantasybyte.com' => 'fantasybyte.com']);
        $instance->setSubject('Letter From Whitechpael');
        return $instance;
    }

    /** @var {string} text message */
    protected $text;

    /**
     * Set Message Text
     * @param {string} $text
     * @return \SwiftMessage
     */
    public function setText($text) {
        $this->setBody($text, 'text/plain');
        $this->text = $text;
        return $this;
    }

    /** @var {string} html of message */
    protected $html;

    /**
     * Set Message Html
     * @param {string} $html
     * @return \SwiftMessage
     */
    public function setHtml($html) {
        $this->addPart($html, 'text/html');
        $this->html = $html;
        return $this;
    }

    /**
     * Set Message Text by Template
     * (Text template should be placed in swiftmailer/text/ directory)
     * @param {string} $template
     * @param (array) $data
     * @return \SwiftMessage
     */
    public function setTextTemplate($template, $data) {
        return
            $this->setText(View::factory('/email/text/' . $template, $data));
    }

    /**
     * Set Message Html by Template.
     * (Html template should be placed in swiftmailer/html/ directory)
     * @param {string} $template
     * @param (array) $data
     * @return \SwiftMessage
     */
    public function setHtmlTemplate($template, $data) {
        return $this->setHtml(View::factory('/email/html/' . $template, $data));
    }

    /**
     * Store message in Database
     */
    public function store() {
        $mail = DAO::factory('Mail', null);
        $mail->text = $this->text;
        $mail->html = $this->html;
        $mail->to = $this->getTo();
        $mail->from = $this->getFrom();
        $mail->subject = $this->getSubject();
        $mail->save();
    }

    public function send(){

        // Create the Transport
        $transport = $this->getTransport();

        // Create the Mailer using your created Transport
        $mailer = Swift_Mailer::newInstance($transport);

        return $mailer->send($this);
    }
}
