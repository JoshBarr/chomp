<?php

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('Springload', __DIR__.'/../lib');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\DBAL\Schema\Table;
use Springload\ClientList;
use Springload\ClientProjectList;
use Springload\ClientProject;
use Springload\ImageList;
use Springload\UserProvider;
use Springload\ProjectController;



$app = new Silex\Application();
$app['debug'] = true;

$app['config'] = array(
    'clients_path' => 'clients',
    'asset_path' => 'assets',
    'clients' => __DIR__ . "/clients/"
);



// Twig Init (relative to this php file)
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../templates',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../var/app.db',
    ),
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

// ----------------------------------------------------------------------------
// Auth setup
// ----------------------------------------------------------------------------

function password($app, $phrase) {
    $user = new User('bob', $phrase);
    $encoder = $app['security.encoder_factory']->getEncoder($user);
    $password = $encoder->encodePassword($phrase, $user->getSalt());
    return $password;
}

// Hmm, looks like you'd really need the App instance as a class static
// or something... can't force the instance in using Silex\Application in a
// closure. Sadface.
function getUser($db, $id) {
    $queryBuilder = $db->createQueryBuilder();
    $queryBuilder
        ->select('u.id', 'u.username', 'u.roles', 'u.paths')
        ->from('users', 'u')
        ->where('u.id = ?');
    return $db->fetchAssoc($queryBuilder, array((int) $id));
}

function protectUrl($app, $url) {
    // $token = $app['security']->getToken();
    // $user = $token->getUser();
    // $paths = $token->getUser()->paths;
    // if ($paths !== "*" && $paths !== "") {
    //     preg_match("#" . $paths . "#", $url, $matches);
    //     if (sizeof($matches) === 0) {
    //         return false;
    //     }
    // }
    return true;
}


$schema = $app['db']->getSchemaManager();
if (!$schema->tablesExist('users')) {
    $users = new Table('users');
    $users->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
    $users->setPrimaryKey(array('id'));
    $users->addColumn('username', 'string', array('length' => 32));
    $users->addUniqueIndex(array('username'));
    $users->addColumn('password', 'string', array('length' => 255));
    $users->addColumn('roles', 'string', array('length' => 255));
    $users->addColumn('paths', 'string', array('length' => 255));
    $users->addColumn('logins', 'integer', array('unsigned' => true));
    $users->addColumn('last_login', 'integer', array('unsigned' => true));

    $schema->createTable($users);


    $app['db']->insert('users', array(
        'username' => 'test',
        'password' => password($app, 'test'),
        'roles' => 'ROLE_USER',
        'paths' => '*'
    ));

    $app['db']->insert('users', array(
        'username' => 'admin',
        'password' => password($app, 'admin'),
        'roles' => 'ROLE_ADMIN',
        'paths' => '*'
    ));

}


// Load app firewalls. These are first-come, first-served.
$fire = array(
    'login' => array(
        'pattern' => '^/login$',
    ),
    'admin' => array(
        'pattern' => '^/.*$',
        'http' => true,
        'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
        'logout' => array('logout_path' => '/logout'),
        'users' => $app->share(function () use ($app) {
            return new Springload\UserProvider($app['db']);
        })
    ),
);

$all_users = $app['db']->fetchAll("SELECT * FROM users");

foreach($all_users as $user) {
    $name = strtolower($user['username']);
    if ($name === "admin") continue;
    $fire[$name] = array(
        'pattern' => $user['paths'],
        'http' => true,
        'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
        'logout' => array('logout_path' => '/logout'),
        'users' => array(
            $name => array($user['roles'], $user['password'])
        )
    );


}


$app['security.firewalls'] = $fire;


$app['security.access_rules'] = array(
    array('^/admin', 'ROLE_ADMIN'),
    array('^/edit', 'ROLE_ADMIN'),
    array('^/.*$', 'ROLE_USER')
);

$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
);






// ----------------------------------------------------------------------------
// Routes
// ----------------------------------------------------------------------------

// Register routes
$app->get('/', function () use ( $app ) {

    $allClients = new Springload\ClientList($app['config']['clients']);

    return $app['twig']->render("homepage.twig", array(
        "directories" => $allClients->getData()
    ));
})
->bind("home");


// ----------------------------------------------------------------------------
// Auth
// ----------------------------------------------------------------------------
$app->get('/login', function(Request $request) use ($app) {
    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/admin', function(Request $request) use ($app) {
    return $app['twig']->render('admin.twig', array(
    ));
});

$app->get('/admin/users', function(Request $request) use ($app) {
    $sql = "SELECT * FROM users";
    $users = $app['db']->fetchAll($sql);

    return $app['twig']->render('admin.twig', array(
        'action' => 'users',
        'subaction' => 'view',
        'users' => $users
    ));
});


$app->get('/admin/users/new', function(Request $request) use ($app) {
    return $app['twig']->render('admin.twig', array(
        'action' => 'users',
        'subaction' => 'new',
        'user' => ''
    ));
});

$app->post('/admin/users/new', function(Request $request) use ($app) {

    $username   = $request->get('username');
    $paths      = $request->get('paths');
    $pass1      = $request->get('password');
    $pass2      = $request->get('password-again');

    if (strlen($pass1) == 0 || strlen($pass2) == 0 || $pass1 !== $pass2) {
        return $app->redirect("/admin/users/new");
    }

    $app['db']->insert('users', array(
      'username' => $username,
      'password' => password($app, $pass1),
      'roles' => 'ROLE_USER',
      'paths' => $paths
    ));

    return $app->redirect("/admin/users");
});



$app->get('/admin/users/{action}/{id}', function(Request $request, $action, $id) use ($app) {

    $user = getUser($app['db'], $id);

    if ($action == "delete") {
        if (strpos($user['roles'], "ADMIN") === false) {
            $user = $app['db']->delete('users', array('id' => $id));
        }

        return $app->redirect("/admin/users");
    }

    return $app['twig']->render('admin.twig', array(
        'action' => 'users',
        'subaction' => $action,
        'user' => $user
    ));
});


$app->post('/admin/users/{action}/{id}', function(Request $request, $action, $id) use ($app) {
    $safe_id = $app['db']->quoteIdentifier($id);

    if ($action == "edit") {
        $username   = $request->get('username');
        $paths      = $request->get('paths');
        $pass1      = $request->get('password');
        $pass2      = $request->get('password-again');

        $values = array(
            'username' => (string) $username,
            'paths' => $paths,
        );

        if (strlen($pass1) > 0 && strlen($pass2)) {
            if ($pass1 !== $pass2) {
                return $app->redirect("/admin/users/edit/$id");
            }
            $values['password'] = password($app, $pass1);
        }


        $app['db']->update('users', $values, array('id' => $id));
    }

    return $app->redirect("/admin/users");
});





// ----------------------------------------------------------------------------
// Editing
// ----------------------------------------------------------------------------

$app->match('/edit/{client}/', function (Request $request, $client="", $project="") use ( $app ) {

    $clientObject = new Springload\ClientProjectList($app['config']['clients'], $client);

    $data = $clientObject->getData();

    $form = $app['form.factory']->createBuilder('form', $data)
        ->add('name')
        ->add('logo')
        ->add('url', 'text', array(
            "constraints" => new Assert\Url()
            ))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $data = $form->getData();

        $clientObject->update($data);

        // redirect somewhere
        return $app->redirect('/project/' . $client);
    }

    return $app['twig']->render("edit-client.twig", array(
        'name' => $client,
        'client' => $clientObject->getData(),
        'form' => $form->createView()
    ));
});




$app->match('/edit/{client}/{project}/', function (Request $request, $client="", $project="") use ( $app ) {

    $clientObject = new Springload\ClientProjectList($app['config']['clients'], $client);
    $projectObject = new Springload\ClientProject($clientObject, $project);
    $data = $projectObject->getData();


    $form = $app['form.factory']->createBuilder('form', array(
        "name" => $data["name"],
        "description" => $data["description"],
        "job_code" => $data["job_code"],
        "preview_url" => $data["preview_url"],
        "basecamp_url" => $data["basecamp_url"]
    ))
        ->add('name')
        ->add('description', 'textarea')
        ->add('job_code')
        ->add('preview_url', 'text', array(
            'required'=> false
        ))
        ->add('basecamp_url')
        ->add('groups', 'hidden', array(
                'data'   => json_encode($data['blocks'])
                )
            )
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
         $result = $form->getData();
         $result['groups'] = json_decode($result["groups"], true);

        $projectObject->saveData($result);

        // redirect somewhere
        return $app->redirect('/project/' . $client . "/" . $project);
    }

    return $app['twig']->render("edit-project.twig", array(
        'name' => $client,
        'project' => $project,
        'client' => $clientObject->getData(),
        'form' => $form->createView()
    ));
});




// ----------------------------------------------------------------------------
// Client/project view methods
// - Check if user can view current path.
// ----------------------------------------------------------------------------


$app->get('/project/{client}/', function ($client) use ( $app ) {

    if (!protectUrl($app, $client)) {
        return $app->redirect('/login');
    }

    $clientObject = new Springload\ClientProjectList($app['config']['clients'], $client);

    return $app['twig']->render("project-list.twig", array(
        'name' => $client,
        'client' => $clientObject->getData()
    ));
})
->bind('client');


$app->get('/project/{client}/{project}', "Springload\ProjectController::redirect");
$app->get('/project/{client}/{project}/', "Springload\ProjectController::index");
$app->get('/project/{client}/{project}/{args}', "Springload\ProjectController::sequence")
->assert("args", ".*");

// ----------------------------------------------------------------------------
// Go!
// ----------------------------------------------------------------------------
$app->run();