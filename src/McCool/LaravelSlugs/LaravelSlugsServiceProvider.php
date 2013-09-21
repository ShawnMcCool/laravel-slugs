<?php namespace McCool\LaravelSlugs;

use Illuminate\Support\ServiceProvider;
use Event, Redirect, Route;

class LaravelSlugsServiceProvider extends ServiceProvider
{
	public function boot()
	{
        $this->package('mccool/laravel-slugs');

        Event::listen('eloquent.created: *', function($model, $event) {
            if ($model instanceOf SlugInterface && ! empty($model->getSlugString())) {
            	$generator = new SlugGenerator($model);
            	$generator->updateSlug();
            }
        });

        Event::listen('eloquent.updated: *', function($model, $event) {
            if ($model instanceOf SlugInterface && ! empty($model->getSlugString())) {
            	$generator = new SlugGenerator($model);
            	$generator->updateSlug();
            }
        });

        $app = $this->app;

		Route::filter('handle_slug', function($route, $request) use ($app) {
		    $action     = $route->getAction();
		    $parameters = $route->getParameters();

		    $slugModel = new Slug;
		    $slug = $slugModel->getByString($parameters['slug']);

		    if ( ! $slug) {
		    	throw new SlugNotFoundException("Could not find the slug {$parameters['slug']}.");
		    }

		    // redirect from historical slug to primary
		    if ( ! $slug->primary) {
		        $primarySlug = $slugModel->getPrimarySlugByHistoricalSlug($slug);

		        if ($primarySlug) {
		            return Redirect::action($action, [$primarySlug->slug], 301);
		        }
		    }

		    $parameters['slug'] = $slug->model;

            $app['slugModel'] = $slug->model;

		    $route->setParameters($parameters);
		});
	}

	public function register()
    {

    }
}