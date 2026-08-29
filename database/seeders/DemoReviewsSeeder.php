<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * تقييمات للحجوزات المكتملة فقط (نفس شرط ReviewController)،
 * وتقييم واحد كحد أقصى لكل حجز.
 */
class DemoReviewsSeeder extends Seeder
{
    /** تعليقات جاهزة لكل مستوى تقييم. */
    private const COMMENTS = [
        5 => [
            'إقامة ممتازة من كل النواحي، الغرفة نظيفة والطاقم متعاون جداً. أنصح فيه بشدة.',
            'تجربة رائعة، الموقع ممتاز والإفطار متنوع. رح أعيد الحجز أكيد.',
            'خدمة الغرف سريعة والاستقبال محترف، أفضل إقامة بهالمدينة.',
        ],
        4 => [
            'الفندق جيد جداً والخدمة ممتازة، بس الواي فاي كان ضعيف بالطابق الأخير.',
            'غرفة مريحة وهادئة، الإفطار كان ممكن يكون أفضل بشوي.',
            'قيمة ممتازة مقابل السعر، والموظفون لطيفون.',
        ],
        3 => [
            'إقامة مقبولة، النظافة جيدة لكن التكييف كان مزعج بالليل.',
            'الموقع ممتاز، لكن الغرفة أصغر مما توقعت.',
            'خدمة عادية، لا شيء سيئ ولا شيء مميز.',
        ],
        2 => [
            'التأخير بتسليم الغرفة كان طويل، والصيانة بطيئة بالاستجابة.',
            'الصور أحلى من الواقع، والضجيج من الشارع مزعج.',
        ],
        1 => [
            'تجربة غير موفقة، الغرفة ما كانت جاهزة والخدمة ضعيفة جداً.',
        ],
    ];

    /**
     * توزيع التقييمات (الأغلبية إيجابية كما هو الحال واقعياً).
     * يتم المرور عليها بالترتيب لكل حجز مكتمل.
     */
    private const RATINGS = [5, 4, 5, 3, 4, 5, 4, 2, 5, 4, 3, 5, 4, 1, 5];

    public function run(): void
    {
        $userIds = DemoUsersSeeder::normalUsers()->pluck('id');

        $bookings = Booking::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->whereDoesntHave('review')
            ->orderBy('id')
            ->get();

        $created = 0;

        foreach ($bookings as $booking) {
            // ~ 3 من كل 4 حجوزات مكتملة تحصل على تقييم.
            // القرار مبني على معرّف الحجز حتى لا تتغيّر النتيجة عند إعادة التشغيل.
            $index = $booking->id;

            if ($index % 4 === 3) {
                continue;
            }

            $rating  = self::RATINGS[$index % count(self::RATINGS)];
            $options = self::COMMENTS[$rating];

            $reviewDate = $booking->check_out_date->copy()->addDays(1 + ($index % 4));

            if ($reviewDate->isFuture()) {
                $reviewDate = now();
            }

            $review = Review::create([
                'user_id'     => $booking->user_id,
                'hotel_id'    => $booking->hotel_id,
                'booking_id'  => $booking->id,
                'comment'     => $options[$index % count($options)],
                'rating'      => $rating,
                'review_date' => $reviewDate->toDateString(),
            ]);

            $review->forceFill(['created_at' => $reviewDate, 'updated_at' => $reviewDate])->saveQuietly();

            $created++;
        }

        $average = Review::whereIn('user_id', $userIds)->avg('rating');

        $this->command?->info(sprintf(
            '  ✔ تقييمات: %d تقييم لحجوزات مكتملة (متوسط %s / 5)',
            $created,
            number_format((float) $average, 2)
        ));
    }
}
