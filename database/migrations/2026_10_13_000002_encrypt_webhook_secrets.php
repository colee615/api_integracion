<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->text('secret')->change();
        });

        DB::table('webhook_endpoints')->orderBy('id')->chunkById(250, function ($endpoints): void {
            foreach ($endpoints as $endpoint) {
                $secret = (string) $endpoint->secret;

                try {
                    Crypt::decryptString($secret);
                } catch (DecryptException) {
                    DB::table('webhook_endpoints')->where('id', $endpoint->id)->update([
                        'secret' => Crypt::encryptString($secret),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('webhook_endpoints')->orderBy('id')->chunkById(250, function ($endpoints): void {
            foreach ($endpoints as $endpoint) {
                try {
                    $secret = Crypt::decryptString((string) $endpoint->secret);
                } catch (DecryptException) {
                    continue;
                }

                DB::table('webhook_endpoints')->where('id', $endpoint->id)->update([
                    'secret' => $secret,
                ]);
            }
        });
    }
};
