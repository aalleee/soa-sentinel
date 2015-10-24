<?php namespace SleepingOwl\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Filesystem\Filesystem;
use SleepingOwl\Admin\Model\Permission as PermissionModel;


class InstallCommand extends Command
{

	/**
	 * The console command name.
	 * @var string
	 */
	protected $name = 'admin:install';
	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Install the admin package';

	/**
	 * Execute the console command.
	 * @return void
	 */
	public function fire()
	{
		$title = $this->option('title');
		$path = $this->option('path');

		$this->call('vendor:publish', ['--provider' => 'SleepingOwl\Admin\AdminServiceProvider']);
		$this->call('vendor:publish', ['--provider' => 'Cartalyst\Sentinel\Laravel\SentinelServiceProvider']);
		$this->call('vendor:publish', ['--provider' => 'Proengsoft\JsValidation\JsValidationServiceProvider']);
		$this->call('elfinder:publish');
		$this->call('vendor:publish', ['--provider' => 'Barryvdh\Elfinder\ElfinderServiceProvider']);
		$this->removeLaravelAuth();
		$this->publishDB();
		$this->publishConfig($title);

		$this->createBootstrapDirectory();
		$this->createMenuFile();
		$this->createBootstrapFile();
		$this->createRoutesFile();
		$this->createUserAndRoleFile();

		$this->createPublicDefaultStructure($path);

		$this->createAdminUserAndRole();
	}

	/**
	 * Migrate database and default seed
	 */
	protected function publishDB()
	{

		$this->call('migrate');

		/*$this->call('db:seed', [
			'--class' => 'SleepingOwl\\Admin\\Database\\Seeders\\SentinelSeeder'
		]);*/
	}

	/**
	 * Create bootstrap directory
	 */
	protected function createBootstrapDirectory()
	{
		$directory = config('admin.bootstrapDirectory');

		if ( ! is_dir($directory))
		{
			$this->laravel['files']->makeDirectory($directory, 0755, true, true);
			$this->line('<info>Admin bootstrap directory was created:</info> ' . str_replace(base_path(), '', $directory));
		}
	}

	/**
	 * Create default menu file
	 */
	protected function createMenuFile()
	{
		$file = config('admin.bootstrapDirectory') . '/menu.php';
		if ( ! file_exists($file))
		{
			$contents = $this->laravel['files']->get(__DIR__ . '/stubs/menu.stub');
			$this->laravel['files']->put($file, $contents);
			$this->line('<info>Menu file was created:</info> ' . str_replace(base_path(), '', $file));
		}
	}

	/**
	 * Create default bootstrap file
	 */
	protected function createBootstrapFile()
	{
		$file = config('admin.bootstrapDirectory') . '/bootstrap.php';
		if ( ! file_exists($file))
		{
			$contents = $this->laravel['files']->get(__DIR__ . '/stubs/bootstrap.stub');
			$this->laravel['files']->put($file, $contents);
			$this->line('<info>Bootstrap file was created:</info> ' . str_replace(base_path(), '', $file));
		}
	}

	/**
	 * Create default routes file
	 */
	protected function createRoutesFile()
	{
		$file = config('admin.bootstrapDirectory') . '/routes.php';
		if ( ! file_exists($file))
		{
			$contents = $this->laravel['files']->get(__DIR__ . '/stubs/routes.stub');
			$this->laravel['files']->put($file, $contents);
			$this->line('<info>Routes file was created:</info> ' . str_replace(base_path(), '', $file));
		}
	}

	/**
	 * Create dummy user file
	 */
	protected function createUserAndRoleFile()
	{
		$userFile = config('admin.bootstrapDirectory') . '/User.php';
		if ( ! file_exists($userFile))
		{
			$contents = $this->laravel['files']->get(__DIR__ . '/stubs/User.stub');
			$this->laravel['files']->put($userFile, $contents);
			$this->line('<info>User file was created:</info> ' . str_replace(base_path(), '', $userFile));
		}

		$roleFile = config('admin.bootstrapDirectory') . '/Role.php';
		if ( ! file_exists($roleFile))
		{
			$contents = $this->laravel['files']->get(__DIR__ . '/stubs/Role.stub');
			$this->laravel['files']->put($roleFile, $contents);
			$this->line('<info>Role file was created:</info> ' . str_replace(base_path(), '', $roleFile));
		}

		$permissionFile = config('admin.bootstrapDirectory') . '/Permission.php';
		if ( ! file_exists($permissionFile))
		{
			$contents = $this->laravel['files']->get(__DIR__ . '/stubs/Permission.stub');
			$this->laravel['files']->put($permissionFile, $contents);
			$this->line('<info>Permission file was created:</info> ' . str_replace(base_path(), '', $permissionFile));
		}
	}

	/**
	 * Create public default structure
	 */
	protected function createPublicDefaultStructure($path = null)
	{
		if (is_null($path)) {
			$path = config('admin.filemanagerDirectory');
		} else {
			$file = config_path('admin.php');
			$contents = $this->laravel['files']->get($file);
			$search = "'filemanagerDirectory' =>  'files/'";
			$replace = "'filemanagerDirectory' =>  '" . $path . "/'";
			$contents = str_replace($search, $replace, $contents);
			$this->laravel['files']->put($file, $contents);
		}

		$uploadsDirectory = public_path($path);
		if ( ! is_dir($uploadsDirectory))
		{
			$this->laravel['files']->makeDirectory($uploadsDirectory, 0755, true, true);
		}


	}

	/**
	 * Publish package config
	 * @param string|null $title
	 */
	protected function publishConfig($title = null)
	{
		$file = config_path('admin.php');
		if ( ! is_null($title))
		{
			$contents = $this->laravel['files']->get($file);
			$contents = str_replace('Sleeping Owl administrator', $title, $contents);
			$this->laravel['files']->put($file, $contents);
		}
	}

	protected function removeLaravelAuth() {
		$files = new Filesystem;

        $files->deleteDirectory(app_path('Http/Controllers/Auth'));

        $files->delete(base_path('database/migrations/2014_10_12_000000_create_users_table.php'));
        $files->delete(base_path('database/migrations/2014_10_12_100000_create_password_resets_table.php'));

        $this->info('Laravel Auth removed! Enjoy your fresh start.');
	}

	protected function createAdminUserAndRole() {
		
		$permissions = [

			'superadmin'	=> [
				'default'		=> true,
				'description'	=> 'Super Admin'
			],
			'controlpanel'	=> [
				'default'		=> true,
				'description'	=> 'Access to the Control Panel'
			],
			'admin.users.view'	=> [
				'default'		=> true,
				'description'	=> 'View Users'
			],
			'admin.users.create'	=> [
				'default'		=> true,
				'description'	=> 'Create Users'
			],
			'admin.users.edit'	=> [
				'default'		=> true,
				'description'	=> 'Edit Users'
			],
			'admin.users.destroy'	=> [
				'default'		=> true,
				'description'	=> 'Delete Users'
			],
			'admin.roles.view'	=> [
				'default'		=> true,
				'description'	=> 'View Roles'
			],
			'admin.roles.create'	=> [
				'default'		=> true,
				'description'	=> 'Create Roles'
			],
			'admin.roles.edit'	=> [
				'default'		=> true,
				'description'	=> 'Edit Roles'
			],
			'admin.roles.destroy'	=> [
				'default'		=> true,
				'description'	=> 'Delete Roles'
			],
			'admin.permissions.view'	=> [
				'default'		=> true,
				'description'	=> 'View Permissions'
			],
			'admin.permissions.create'	=> [
				'default'		=> true,
				'description'	=> 'Create Permissions'
			],
			'admin.permissions.edit'	=> [
				'default'		=> true,
				'description'	=> 'Edit Permissions'
			],
			'admin.permissions.destroy'	=> [
				'default'		=> true,
				'description'	=> 'Delete Permissions'
			]

		];

		try
		{
			
			//create admin user
			$credentials = [
			    'email'    => 'admin@soa.backend',
			    'password' => 'password',
			];

			$user = \Sentinel::create($credentials);
			$activation = \Activation::create($user);
			$activation_complete = \Activation::complete($user, $activation->code);

			//create admin role
			$role = \Sentinel::getRoleRepository()->createModel()->create([
			    'name' => 'Administrator',
			    'slug' => 'administrator',
			]);

			$role = \Sentinel::findRoleByName('Administrator');

			//add permissions to role
			foreach ($permissions as $key => $permission) {
				$role->addPermission($key, $permission['default']);
			}

			$role->save();

			//attach admin user to admin role
			$role->users()->attach($user);			

			$this->info('Admin Role and User created successfully.');

			$this->addPermissions($permissions);

		} catch (\Exception $e)
		{
			$this->info('Something went wrong while creating Admin Role and User.');
			$this->error($e);
		}

	}

	protected function addPermissions($permissions) {

		//add permissions to role
		foreach ($permissions as $key => $permission) {
			PermissionModel::create([
				'value' => $key, 
				'description' =>$permission['description']
			]);
		}

		$this->info('Default Permissions created successfully.');

	}

	/**
	 * Get the console command options.
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			[
				'title',
				null,
				InputOption::VALUE_REQUIRED,
				'Title for admin module.'
			],
			[
				'path',
				null,
				InputOption::VALUE_REQUIRED,
				'Path for filemanager directory relative to public directory. Please set no / at the end!'
			],
		];
	}

}