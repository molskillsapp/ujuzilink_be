<?php
/*
 * File name: EProvider.php
 * Last modified: 2022.04.11 at 13:30:03
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2022
 */

namespace App\Models;

use App\Casts\EProviderCast;
use App\Traits\HasTranslations;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Eloquent as Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Facades\Module;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\OpeningHours\OpeningHours;

/**
 * Class EProvider
 * @package App\Models
 * @version January 13, 2021, 11:11 am UTC
 *
 * @property EProviderType eProviderType
 * @property Collection[] users
 * @property Collection[] taxes
 * @property Collection[] addresses
 * @property Collection[] awards
 * @property Collection[] experiences
 * @property Collection eProviderSubscriptions
 * @property Collection[] availabilityHours
 * @property Collection[] eServices
 * @property Collection[] galleries
 * @property integer id
 * @property string name
 * @property integer e_provider_type_id
 * @property string description
 * @property string phone_number
 * @property string mobile_number
 * @property double availability_range
 * @property boolean available
 * @property boolean featured
 * @property boolean accepted
 */
class EProvider extends Model implements HasMedia, Castable
{

    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }
    use HasFactory;
    use HasTranslations;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:127',
        'e_provider_type_id' => 'required|exists:e_provider_types,id',
        'phone_number' => 'max:50',
        'mobile_number' => 'max:50',
        'availability_range' => 'required|numeric|max:9999999.99|min:0.01'
    ];
    public $translatable = [
        'name',
        'description',
    ];
    public $table = 'e_providers';
    public $fillable = [
        'name',
        'e_provider_type_id',
        'description',
        'phone_number',
        'mobile_number',
        'availability_range',
        'available',
        'featured',
        'accepted'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'image' => 'string',
        'name' => 'string',
        'e_provider_type_id' => 'integer',
        'description' => 'string',
        'phone_number' => 'string',
        'mobile_number' => 'string',
        'availability_range' => 'double',
        'available' => 'boolean',
        'featured' => 'boolean',
        'accepted' => 'boolean'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
        'has_media',
        'rate',
        'available',
        'total_reviews',
        'has_valid_subscription'
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
    ];

    /**
     * @return CastsAttributes|CastsInboundAttributes|string
     */
    public static function castUsing(array $arguments): string
    {
        return EProviderCast::class;
    }

    public function discountables()
    {
        return $this->morphMany('App\Models\Discountable', 'discountable');
    }

    /**
     * @param Media|null $media
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 200, 200)
            ->sharpen(10);

        $this->addMediaConversion('icon')
            ->fit(Manipulations::FIT_CROP, 100, 100)
            ->sharpen(10);
    }


    /**
     * to generate media url in case of fallback will
     * return the file type icon
     * @param string $collectionName
     * @param string $conversion
     * @return string
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversion = ''): string
    {
        $url = $this->getFirstMediaUrlTrait($collectionName);
        $array = explode('.', $url);
        $extension = strtolower(end($array));
        if (in_array($extension, config('media-library.extensions_has_thumb'))) {
            return asset($this->getFirstMediaUrlTrait($collectionName, $conversion));
        } else {
            return asset(config('media-library.icons_folder') . '/' . $extension . '.png');
        }
    }

    public function getCustomFieldsAttribute()
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []));
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()
            ->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')
            ->where('custom_fields.in_table', '=', true)
            ->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    public function customFieldsValues()
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }

    /**
     * Provider ready when he is accepted by admin and marked as available
     * and is open now
     */
    public function getAvailableAttribute(): bool
    {
        return $this->accepted && $this->attributes['available'] && $this->openingHours()->isOpen();
    }

    public function openingHours(): OpeningHours
    {
        $openingHoursArray = [];
        foreach ($this->availabilityHours as $element) {
            $openingHoursArray[$element['day']] = [
                'data' => $element['data'],
                $element['start_at'] . '-' . $element['end_at']
            ];
        }
        return OpeningHours::createAndMergeOverlappingRanges($openingHoursArray);
    }

    /**
     * get each range of 30 min with open/close provider
     */
    public function weekCalendarRange(Carbon $date): array
    {
        $period = CarbonPeriod::since($date->subDay()->ceilDay()->subMinutes(30))->minutes(30)->until($date->addDay()->ceilDay()->subMinutes(60));
        $dates = [];
        // Iterate over the period
        foreach ($period as $key => $d) {
            $firstDate = $d->locale('en')->toDateTime();
            $secondDate = $d->locale('en')->addMinutes(30)->toDateTime();
            $isOpen = $this->openingHours()->isOpenAt($firstDate) || $this->openingHours()->isOpenAt($secondDate);
            $times = [$d->locale('en')->toIso8601String()];
            $times[] = $d->locale('en')->addMinutes(30)->toIso8601String();
            $dates[] = [$times, $isOpen];
        }
        return $dates;
    }

    /**
     * get each 15 min with open/close provider
     */
    public function weekCalendar(Carbon $date): array
    {
        $period = CarbonPeriod::since($date->subDay()->ceilDay())->minutes(15)->until($date->addDay()->ceilDay()->subMinutes(15));
        $dates = [];
        // Iterate over the period
        foreach ($period as $key => $d) {
            $firstDate = $d->locale('en')->toDateTime();
            $isOpen = $this->openingHours()->isOpenAt($firstDate);
            $times = $d->locale('en')->toIso8601String();
            $dates[] = [$times, $isOpen];
        }
        return $dates;
    }

    public function getRateAttribute(): float
    {
        return (float)$this->eProviderReviews()->avg('rate');
    }

    /**
     * @return HasManyThrough
     **/
    public function eProviderReviews()
    {
        return $this->hasManyThrough(EServiceReview::class, EService::class);
    }

    public function getHasValidSubscriptionAttribute(): ?bool
    {
        if (!Module::isActivated('Subscription')) {
            return null;
        }
        $result = $this->eProviderSubscriptions
            ->where('expires_at', '>', now())
            ->where('starts_at', '<=', now())
            ->where('active', '=', 1)
            ->count();
        return $result > 0;
    }

    public function getTotalReviewsAttribute(): float
    {
        return (int)$this->eProviderReviews()->count();
    }

    /**
     * @return BelongsTo
     **/
    public function eProviderType()
    {
        return $this->belongsTo(EProviderType::class, 'e_provider_type_id', 'id');
    }

    /**
     * @return HasMany
     **/
    public function awards()
    {
        return $this->hasMany(Award::class, 'e_provider_id');
    }

    /**
     * @return HasMany
     **/
    public function experiences()
    {
        return $this->hasMany(Experience::class, 'e_provider_id');
    }

    /**
     * @return null|HasMany
     *
     * @throws ModuleNotFoundException
     */
    public function eProviderSubscriptions(): HasMany
    {
        if (Module::isActivated('Subscription'))
            return $this->hasMany('Modules\Subscription\Models\EProviderSubscription', 'e_provider_id');
        else
            throw new ModuleNotFoundException();

    }

    /**
     * @return HasMany
     **/
    public function availabilityHours(): HasMany
    {
        return $this->hasMany(AvailabilityHour::class, 'e_provider_id')->orderBy('start_at');
    }

    /**
     * @return HasMany
     **/
    public function eServices()
    {
        return $this->hasMany(EService::class, 'e_provider_id');
    }

    /**
     * @return HasMany
     **/
    public function galleries()
    {
        return $this->hasMany(Gallery::class, 'e_provider_id');
    }

    /**
     * @return BelongsToMany
     **/
    public function users()
    {
        return $this->belongsToMany(User::class, 'e_provider_users');
    }

    /**
     * @return BelongsToMany
     **/
    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'e_provider_addresses');
    }

    /**
     * @return BelongsToMany
     **/
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'e_provider_taxes');
    }

    /**
     * Add Media to api results
     * @return bool
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('image');
    }
}
