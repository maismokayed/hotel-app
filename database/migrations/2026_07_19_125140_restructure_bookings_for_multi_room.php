<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) نضيف hotel_id لجدول bookings (nullable مؤقتًا لحتى نعبيها من القديم)
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('hotel_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // 2) تعبئة hotel_id من الغرفة المرتبطة بكل حجز قديم
        DB::table('bookings')
            ->whereNotNull('room_id')
            ->orderBy('id')
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) {
                    $hotelId = DB::table('rooms')->where('id', $booking->room_id)->value('hotel_id');

                    if ($hotelId) {
                        DB::table('bookings')
                            ->where('id', $booking->id)
                            ->update(['hotel_id' => $hotelId]);
                    }
                }
            });
        // 3) جدول الحجز-الغرف
        Schema::create('booking_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['booking_id', 'room_id']);
        });

        // 4) نقل بيانات الحجوزات القديمة إلى الجدول الجديد
        $oldBookings = DB::table('bookings')->whereNotNull('room_id')->get(['id', 'room_id']);
        $now = now();
        $rows = $oldBookings->map(fn($b) => [
            'booking_id' => $b->id,
            'room_id'    => $b->room_id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($rows)) {
            DB::table('booking_room')->insert($rows);
        }

        // 5) حذف room_id من bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
        });

        // 6) تنظيف حالة "booked" القديمة (صارت غير مستخدمة)
        DB::table('rooms')->where('status', 'booked')->update(['status' => 'available']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->constrained()->cascadeOnDelete();
        });

        $bookingRooms = DB::table('booking_room')->orderBy('id')->get();
        $seen = [];
        foreach ($bookingRooms as $br) {
            if (! isset($seen[$br->booking_id])) {
                DB::table('bookings')->where('id', $br->booking_id)->update(['room_id' => $br->room_id]);
                $seen[$br->booking_id] = true;
            }
        }

        Schema::dropIfExists('booking_room');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->dropColumn('hotel_id');
        });
    }
};
