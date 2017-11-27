<?php

namespace App\Repositories\Backend\Videos;

use App\Models\Admin\Video\Tag;
use App\Repositories\BaseRepository;
/**
 * Class VideosRepository.
 * 
 * @category Videos
 * @package  Videos
 */
class TagsRepository extends BaseRepository
{
    
    /**
     * Listing of Tags
    */
    public function listing()
    {
        $tag = new Tag;
        $tagInfo = $tag->where('type', 1)->get();
        return $tagInfo;
    }       
}
