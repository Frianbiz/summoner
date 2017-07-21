<?php

namespace Frianbiz\Summoner\Commands;

use Illuminate\Console\Command;

class Summon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summon:creature';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summon application\'s creatures';

    protected $creature;

    protected $views;

    protected $fields;

	/**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->creature = str_plural($this->ask('Which creature?'));
        $this->fields = [];

        do {
       
            $field = trim($this->ask('Field?'));
            $type = $this->anticipate('Type?', ['string', 'integer']);
            $this->fields[] = [
                'name' => $field,
                'type' => $type
            ];

        } while ($this->confirm('Another ?'));

        //dd($fields);
        $headers = ['Field', 'Type'];

        $this->table($headers, $this->fields);

        $this->views = [
            'index.stub' => $this->creature . '/index.blade.php'
        ];

        $this->createDirectories();
     
        $this->exportViews();

        $this->exportMigration();

        $this->exportModel();

        file_put_contents(
            app_path('Http/Controllers/' . title_case($this->creature) . 'Controller.php'),
            $this->compileControllerStub()
        );

        file_put_contents(
            base_path('routes/web.php'),
            $this->compileRoutesStub(),
            FILE_APPEND
        );

        $this->info($this->creature . ' summoned successfully!');
    }

    protected function createDirectories()
    {
        if (! is_dir(resource_path('views/' . $this->creature))) {
            mkdir(resource_path('views/' . $this->creature), 0755, true);
        }
    }

    protected function exportViews()
    {
        // generateIndex
        // generateCreate
        file_put_contents(
            resource_path('views/'.$this->creature.'/index.blade.php'),
            $this->compileViewStub(__DIR__.'/../stubs/views/index.stub')
        );

        $fields = '';
        foreach ($this->fields as $field)
        {
            if ($field['type'] == 'string') {
                $fields .= file_get_contents(__DIR__.'/../stubs/views/partials/form/field.stub');
                $fields = str_replace('{{fieldName}}', $field['name'], $fields);
                $fields = str_replace('{{fieldType}}', 'text', $fields);
            }
        }

        $create = str_replace(
            '{{fields}}',
            $fields,
            file_get_contents(__DIR__.'/../stubs/views/create.stub')
        );

        $create = str_replace(
            '{{pluralCamel}}',
            $this->creature,
            $create
        );

        file_put_contents(
            resource_path('views/'.$this->creature.'/create.blade.php'),
            $create
        );

    }

    protected function exportMigration()
    {
        file_put_contents(
            database_path('migrations/'.date('Y_m_d_His').'_create_'.$this->creature.'_table.php'),
            $this->compileMigrationStub()
        );
    }

    protected function exportModel()
    {
        file_put_contents(
            app_path(ucfirst(str_singular($this->creature)).".php"),
            $this->compileModelStub()
        );
    }

    protected function compileModelStub()
    {
        $model = file_get_contents(__DIR__.'/../stubs/model.stub');
        $className = ucfirst(str_singular($this->creature));

        $fillable = "[\r\t\t";
        foreach ($this->fields as $field) {
            $fillable .= "'".$field['name']."',";
            $fillable .= "\r\t\t";
        }
        $fillable .= "\r\t]";


        $model = str_replace('{{className}}', $className, $model);
        $model = str_replace('{{fillable}}', $fillable, $model);

        return $model;
    }

    protected function compileViewStub($view)
    {
        return str_replace(
            '{{creature}}',
            $this->creature,
            file_get_contents($view)
        );
    }

    

    protected function compileControllerStub()
    {
        $className = ucfirst(str_singular($this->creature));

        $controller = file_get_contents(__DIR__.'/../stubs/controller.stub');
        $controller = str_replace('{{className}}', $className, $controller);
        $controller = str_replace('{{creature}}', $this->creature, $controller);

        return $controller;
    }

    protected function compileRoutesStub()
    {
        return str_replace(
            '{{creature}}',
            $this->creature,
            file_get_contents(__DIR__.'/../stubs/routes.stub')
        );
    }

    protected function compileMigrationStub()
    {
        $pluralCamel = ucfirst($this->creature);
        $pluralSnake = snake_case($this->creature);
        
        $migration = file_get_contents(__DIR__.'/../stubs/migration.stub');

        $fields = '';
        foreach ($this->fields as $field) {
            $fields .= '$table->'.$field['type'].'(\''.$field['name'].'\');';
            $fields .= "\r\t\t\t";
        }

        $migration = str_replace('{{fields}}', $fields, $migration);
        $migration = str_replace('{{pluralCamel}}', $pluralCamel, $migration);
        $migration = str_replace('{{pluralSnake}}', $pluralSnake, $migration);

        return $migration;
    }
}