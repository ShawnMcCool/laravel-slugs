<?php namespace McCool\LaravelSlugs;

class SlugGenerator
{
    protected $model;

    public function __construct(SlugInterface $model)
    {
        $this->model = $model;
    }

    public function updateSlug()
    {
        if ($this->hasSlug() and $this->slugHasntChanged()) return;
        if ($this->hasSlug()) $this->archiveSlug();
        return $this->generateSlug();
    }

    protected function hasSlug()
    {
        return (bool) $this->model->slug;
    }

    protected function slugHasntChanged()
    {
        return $this->model->slug->slug == $this->model->getSlugString();
    }

    protected function archiveSlug()
    {
        $slug = $this->model->slug;

        $slug->primary = 0;
        $slug->save();
    }

    protected function generateSlug()
    {
        $modelClass = get_class($this->model);
        $slugString = $this->getUniqueSlugString($this->model->getSlugString(), $this->model->id, $modelClass);
        $this->removeHistoricalSlug($slugString);

        return Slug::create([
            'slug'       => $slugString,
            'owner_id'   => $this->model->id,
            'owner_type' => $modelClass,
            'primary'    => 1,
        ]);
    }

    protected function getUniqueSlugString($slugString, $id, $modelClass)
    {
        $padding = '';
        $slug = '';

        do {
            $slug = $slugString . $padding;

            $slugFound = Slug::where('primary', '=', 1)->where('slug', '=', $slug)->where(function($q) use ($id, $modelClass) {
                $q->where('owner_id', '!=', $id);
                $q->orWhere('owner_type', '!=', $modelClass);
            })->first();

            if ($slugFound) {
                $padding = "-{$id}";
            }
        } while($slugFound);

        return $slug;
    }

    protected function removeHistoricalSlug($slugString)
    {
        $historicalSlug = Slug::where('slug', '=', $slugString)->where('primary', '=', 0)->delete();
    }
}