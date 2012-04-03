# QueueEmail

QueueEmail is a CakePHP plugin that allows you to queue emails in your database
instead of sending them directly out. This is helpful for sending large batches
of emails via a CRON job.

## Usage

Create the table

    cake schema create QueueEmail.queue

Use the QueueEmail component

    var $components = array('QueueEmail.QueueEmail');

Then send your emails as you normally would

    $this->QueueEmail->to = 'test@test.com';
    $this->QueueEmail->from = 'test@test.com';
    $this->QueueEmail->subject = 'A queued Email';
    $this->QueueEmail->send();

Instead of sending, the email will be stored in the database. You can do anything
you would do on the built-in Email component with QueueEmail, such as sending
via smtp, attaching files, etc.

When you're ready to send, use the shell provided. I recommend setting up a CRON
job to execute the shell every 5 minutes.

    /path/to/site/cake/console/cake -app "/path/to/site/app" queue_sender send

You can also send individual emails by passing the id directly after the send 
method:

    /path/to/site/cake/console/cake -app "/path/to/site/app" queue_sender send 12

If you have emails you don't want to queue, simply change the `$queue` var to
false.

    $this->QueueEmail->queue = false; // this email won't be queued

This will send the email as the regular EmailComponent normally would.

### Options

The shell supports several options, described below. You can pass them directly
via the CLI, or by setting its value in the `Configure` class.

* `batchSize` Limit the number of queued emails sent at time. Default 50.
* `maxTries` Limit the number of send attempts. Default 5.
* `deleteAfter` Remove the record from the db after it sends successfully. Default `true`.

Setting using the shell:
    
    /path/to/site/cake/console/cake -app "/path/to/site/app" queue_sender send -batchSize 100

Setting using `Configure`:

    Configure::write('QueueEmail.batchSize', 100);

## Managing

The QueueEmail plugin also comes with some limited views that allow you to see
what emails are currently queued. Visit `/queue_email/queues/index`

## Annoyances

Much of the information is stored as serialized array strings and I'm not fond
of that. If you have suggestions to avoid this let me know.

## Future

* Add ability to send one or multiple emails from the management console.

## License

Licensed under The MIT License
[http://www.opensource.org/licenses/mit-license.php][1]
Redistributions of files must retain the above copyright notice.

[1]: http://www.opensource.org/licenses/mit-license.php