<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->register(new Silex\Provider\SessionServiceProvider(), array('cookie_lifetime' => 300));
$app['debug'] = true;

ini_set('session.cookie_lifetime', 300);

$dbopts = parse_url(getenv('DATABASE_URL'));
$app->register(new Herrera\Pdo\PdoServiceProvider(),
		array(
				'pdo.dsn' => 'pgsql:dbname='.ltrim($dbopts["path"],'/').';host='.$dbopts["host"] . ';port=' . $dbopts["port"],
				'pdo.username' => $dbopts["user"],
				'pdo.password' => $dbopts["pass"]
		)
		);

$app->get('/', function() use($app) {
	return $app['twig']->render('index.twig', array( 'root'	=> '' ));
});

$app->post('/login', function(Request $request) use($app) {
	$username = $request->request->get('username');
	$password = crypt($request->request->get('password'), '1234');

	$response = array(
			'message' => ''
	);

	try {
		$st = $app['pdo']->prepare('SELECT * FROM sb_users WHERE username = ? AND password = ?');
		$st->execute(array($username, $password));
		$names = $st->fetchAll();
			
		if ( count($names) === 1 ) {
			if (!$app['session']->isStarted()) {
				$app['session']->start();
			}
			$app['session']->set('user', array('username' => $username));
			$response['redirect'] = '/client-portal';
			$response['success'] = true;
			$response['message'] = 'LOGIN SUCCESSFUL';
		} else {
			if ($app['session']->isStarted()) {
				$app['session']->clear();
			}
			$response['success'] = false;
			$response['message'] = 'LOGIN UNSUCCESSFUL';
		}
	} catch (\Exception $e) {
		if ($app['session']->isStarted()) {
			$app['session']->clear();
		}
		$response['success'] = false;
		$response['error'] = $e.getMessage();
	
	}		
	return $app->json($response);
});
	
$app->get('/client-portal/', function (Request $request) use ($app) {
	if (null === $user = $app['session']->get('user')) {
		return $app->redirect('/');
	}
	$directory = '../client-portal/';
	$names = array();
	try {
		$scanned_directory = array_diff(scandir($directory), array('..', '.'));
		foreach ($scanned_directory as $entry ) {
			if(is_dir($directory.'/'.$entry)) {
				$filename = $directory.'/'.$entry.'/portal.json';
				if ( file_exists($filename) ) {
					$file = json_decode(file_get_contents($filename), true);
					$names[] = array_merge( 
								$file, 
								array(
										'url' => $request->getSchemeAndHttpHost().'/client-portal/'.$entry
								)
							);
				}
			}
		}
	} catch (\Exception $e) {
		
		return $e.getMessage();
	}
	return $app['twig']->render('portal.twig', array(
			'root'	=> '/',
			'names' => $names
	));
});

$app->match('/client-portal/{project}', function (Request $request, $project) use ($app) {
	if (null === $user = $app['session']->get('user')) {
		return $app->redirect('/');
	}
	
	return  'under construction: '.$project;
});
	
$app->run();
