<?php
namespace MyPhpApp\Helpers;
include 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
/**
 * Utility class for handling email-related tasks such as creating mailers and composing email bodies and sending email.
 */
class MailUtils {
    /**
     * Creates a mailer instance with the provided configurations.
     *
     * @param string $mailHost       The SMTP host.
     * @param string $mailUsername   The SMTP username.
     * @param string $mailPassword   The SMTP password.
     * @param string $appName        The name of the application.
     * @param string $appEmail       The email address of the application.
     * @return Mailer                A mailer instance.
     * @throws Exception             If mailer creation fails.
     */
    public function createMailer(
        string $mailHost,
        string $mailUsername,
        string $mailPassword,
        string $appName,
        string $appEmail
    ): Mailer {
        try {
            return new Mailer($mailHost, $mailUsername, $mailPassword, $appName, $appEmail);
        } catch (Exception $e) {
            throw new Exception("Could not create mailer. Error: {$e->getMessage()}");
        }
    }

    /**
     * Generates the HTML body for a signup confirmation email.
     *
     * @param string $appName   The name of the application.
     * @param string $name      The recipient's name.
     * @param string $code      The signup confirmation code.
     * @return string           The HTML body of the email.
     */
    public function signupCodeBody1(string $appName, string $name, string $code): string {
        return "
            <html lang='en'>
                <body>
                    <h1>Welcome to $appName!</h1>
                    <p>Hi $name,</p>
                    <p>Confirm your sign-up using the code below:</p>
                    <p>$code</p>
                    <p>Best regards,</p>
                    <p>$appName Team</p>
                </body>
            </html>
        ";
    }
}

/**
 * Represents an email sender using PHPMailer.
 */
class Mailer {
    private object $mail;

    /**
     * Constructs a mailer instance with SMTP configurations.
     *
     * @param string $mailHost       The SMTP host.
     * @param string $mailUsername   The SMTP username.
     * @param string $mailPassword   The SMTP password.
     * @param string $appName        The name of the application.
     * @param string $appEmail       The email address of the application.
     * @throws Exception             If mailer initialization fails.
     */
    public function __construct(
        string $mailHost,
        string $mailUsername,
        string $mailPassword,
        string $appName,
        string $appEmail
    ) {
        $this->mail = new PHPMailer(true);
        try {
            $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mail->isSMTP();
            $this->mail->Host       = $mailHost;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $mailUsername;
            $this->mail->Password   = $mailPassword;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port       = 465;
            $this->mail->setFrom($appEmail, $appName);
        } catch (Exception $e) {
            throw new Exception("Could not initialize mailer. Error: {$this->mail->ErrorInfo}");
        }
    }

    /**
     * Creates an email object with recipient, subject, and HTML body.
     *
     * @param string $recipientEmail   The recipient's email address.
     * @param string $subject          The subject of the email.
     * @param string $htmlBody         The HTML body of the email.
     * @return Mail                    An email object.
     */
    public static function composeEmail(
        string $recipientEmail,
        string $subject,
        string $htmlBody
    ): Mail {
        return new Mail($recipientEmail, $subject, $htmlBody);
    }

    /**
     * Sends emails to multiple recipients.
     *
     * @param array $mails   An array of Mail objects.
     * @throws Exception     If mail sending fails.
     */
    public function sendMail(array $mails): void {
        if (!isset($this->mail)) {
            throw new Exception("Mailer not initialized.");
        }
        try {
            foreach ($mails as $mail) {
                $this->mail->addAddress($mail->recipientEmail);
                $this->mail->isHTML(true);
                $this->mail->Subject = $mail->subject;
                $this->mail->Body = $mail->htmlBody;
                $this->mail->send();
                $this->mail->clearAddresses();
            }
        } catch (Exception $e) {
            throw new Exception("Could not send mail. Error: {$this->mail->ErrorInfo}");
        }
    }

    /**
     * Sends the same email to multiple recipients.
     *
     * @param array  $recipientEmails  An array of recipient email addresses.
     * @param string $subject          The subject of the email.
     * @param string $htmlBody         The HTML body of the email.
     * @throws Exception              If mail sending fails.
     */
    public function sendSameEmailToAll(
        array $recipientEmails,
        string $subject,
        string $htmlBody
    ): void {
        $mails = [];
        foreach ($recipientEmails as $email) {
            $mails[] = Mailer::composeEmail($email, $subject, $htmlBody);
        }
        try {
            $this->sendMail($mails);
        } catch (Exception $e) {
            throw new Exception("Could not send mail. Error: {$e->getMessage()}");
        }
    }
}

/**
 * Represents an email object.
 */
class Mail {
    public string $recipientEmail;
    public string $subject;
    public string $htmlBody;

    /**
     * Constructs an email object with recipient, subject, and HTML body.
     *
     * @param string $recipientEmail   The recipient's email address.
     * @param string $subject          The subject of the email.
     * @param string $htmlBody         The HTML body of the email.
     */
    public function __construct(string $recipientEmail, string $subject, string $htmlBody) {
        $this->recipientEmail = $recipientEmail;
        $this->subject = $subject;
        $this->htmlBody = $htmlBody;
    }
}



