<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Account;
use App\Models\CompletedSubmitter;
use App\Models\EmailMessage;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\TemplateAccess;
use App\Models\TemplateFolder;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelFactoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_application_model_has_a_factory_and_no_phantom_fillable_columns(): void
    {
        foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
            $name = basename($file, '.php');
            $modelClass = 'App\\Models\\'.$name;
            $factoryClass = 'Database\\Factories\\'.$name.'Factory';

            $this->assertTrue(class_exists($factoryClass), 'Factory missing for '.$modelClass);
            $this->assertInstanceOf(Factory::class, $modelClass::factory());

            $model = new $modelClass;
            $columns = Schema::getColumnListing($model->getTable());
            foreach ($model->getFillable() as $column) {
                $this->assertContains($column, $columns, "$modelClass has an unmigrated fillable column: $column");
            }
        }
    }

    #[Test]
    public function default_factories_generate_records_with_consistent_account_relationships(): void
    {
        $folder = TemplateFolder::factory()->create();
        $this->assertSame($folder->account_id, $folder->author->account_id);

        $template = Template::factory()->create();
        $this->assertSame($template->account_id, $template->author->account_id);
        $this->assertSame($template->account_id, $template->folder->account_id);

        $submission = Submission::factory()->create();
        $this->assertSame($submission->account_id, $submission->template->account_id);
        $this->assertSame($submission->account_id, $submission->createdByUser->account_id);

        $submitter = Submitter::factory()->create();
        $this->assertSame($submitter->account_id, $submitter->submission->account_id);

        $completed = CompletedSubmitter::factory()->create();
        $this->assertSame($completed->submission_id, $completed->submitter->submission_id);
        $this->assertSame($completed->account_id, $completed->submitter->account_id);
        $this->assertSame($completed->template_id, $completed->submitter->submission->template_id);

        $event = SubmissionEvent::factory()->create();
        $this->assertSame($event->account_id, $event->submission->account_id);
        $this->assertSame($event->submission_id, $event->submitter->submission_id);

        $email = EmailMessage::factory()->create();
        $this->assertSame($email->account_id, $email->author->account_id);

        $webhook = WebhookEvent::factory()->create();
        $this->assertSame($webhook->account_id, $webhook->webhookUrl->account_id);

        $access = TemplateAccess::factory()->create();
        $this->assertSame($access->user->account_id, $access->template->account_id);
    }

    #[Test]
    public function overriding_account_on_template_and_folder_factories_keeps_owner_consistent(): void
    {
        $account = Account::factory()->create();
        $template = Template::factory()->create(['account_id' => $account->id]);
        $folder = TemplateFolder::factory()->create(['account_id' => $account->id]);

        $this->assertSame($account->id, $template->author->account_id);
        $this->assertSame($account->id, $template->folder->account_id);
        $this->assertSame($account->id, $folder->author->account_id);
    }
}
