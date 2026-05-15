<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Complaint;
use App\Models\ComplaintPower;
use App\Models\ComplaintSource;
use App\Models\LastStep;
use App\Models\Oos;
use App\Models\Platform;
use App\Models\SkuCode;
use App\Models\SubCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::query()->where('name', 'ANTA')->firstOrFail();

        $platforms = [
            'BLIBLI' => Platform::query()->where('name', 'BLIBLI')->firstOrFail(),
            'SHOPEE' => Platform::query()->where('name', 'SHOPEE')->firstOrFail(),
            'TIKTOK' => Platform::query()->where('name', 'TIKTOK')->firstOrFail(),
            'TOKOPEDIA' => Platform::query()->where('name', 'TOKOPEDIA')->firstOrFail(),
        ];

        $sources = [
            'After Sales' => ComplaintSource::query()->where('name', 'After Sales')->firstOrFail(),
            'KAE' => ComplaintSource::query()->where('name', 'KAE')->firstOrFail(),
            'Brand' => ComplaintSource::query()->where('name', 'Brand')->firstOrFail(),
            'Socmed' => ComplaintSource::query()->where('name', 'Socmed')->firstOrFail(),
        ];

        $powers = [
            'Normal Complaint' => ComplaintPower::query()->where('name', 'Normal Complaint')->firstOrFail(),
            'Hard Complaint' => ComplaintPower::query()->where('name', 'Hard Complaint')->firstOrFail(),
        ];

        $subCases = [
            'Bad Service' => SubCase::query()->where('name', 'Bad Service')->firstOrFail(),
            'OOS' => SubCase::query()->where('name', 'OOS')->firstOrFail(),
            'Bad Quality Product' => SubCase::query()->where('name', 'Bad Quality Product')->firstOrFail(),
            'Wrong Product' => SubCase::query()->where('name', 'Wrong Product')->firstOrFail(),
        ];

        $lastSteps = [
            'Follow Up to After Sales Team' => LastStep::query()->where('name', 'Follow Up to After Sales Team')->firstOrFail(),
            'Follow Up KAE to Brand' => LastStep::query()->where('name', 'Follow Up KAE to Brand')->firstOrFail(),
            'Claim Reject' => LastStep::query()->where('name', 'Claim Reject')->firstOrFail(),
            'Seller Win' => LastStep::query()->where('name', 'Seller Win')->firstOrFail(),
        ];

        $agents = [
            'CS ANTA 1' => User::query()->where('name', 'CS ANTA 1')->firstOrFail(),
            'CS Escalation' => User::query()->where('name', 'CS Escalation')->firstOrFail(),
            'Test User' => User::query()->where('name', 'Test User')->firstOrFail(),
        ];

        $skus = [
            'SKU-ANTA-RUN-001' => SkuCode::query()->where('sku', 'SKU-ANTA-RUN-001')->firstOrFail(),
            'SKU-ANTA-BASKET-002' => SkuCode::query()->where('sku', 'SKU-ANTA-BASKET-002')->firstOrFail(),
            'SKU-ANTA-LIFESTYLE-003' => SkuCode::query()->where('sku', 'SKU-ANTA-LIFESTYLE-003')->firstOrFail(),
        ];

        $items = [
            [
                'source' => $sources['After Sales'],
                'tanggal_complaint' => now()->subDays(9)->toDateString(),
                'tanggal_order' => now()->subDays(11)->toDateString(),
                'jam_customer_complaint' => '10:15:00',
                'brand' => $brand,
                'platform' => $platforms['BLIBLI'],
                'sku' => $skus['SKU-ANTA-RUN-001'],
                'sub_case' => $subCases['Bad Service'],
                'last_step' => $lastSteps['Follow Up to After Sales Team'],
                'cs_user' => $agents['CS ANTA 1'],
                'complaint_power' => $powers['Normal Complaint'],
                'order_id' => 'ORD-ANTA-1001',
                'username' => 'first.customer',
                'resi' => 'RESI-ANTA-1001',
                'qty' => 1,
                'cause_by' => 'CS',
                'proof' => 'Screenshot percakapan pelanggan terkait keterlambatan respons.',
                'summary_case' => 'Customer mengeluhkan respons awal yang lambat dari tim CS.',
                'update_long_text' => 'CS melakukan konfirmasi kronologi dan meneruskan isu ke tim after sales.',
                'part_of_bad' => 'Customer service response',
                'step_cs_selesai' => 'NO',
                'tanggal_update' => now()->subDays(8)->toDateString(),
                'complaint_power_name' => 'Normal Complaint',
                'video_unboxing' => 'complaints/videos/sample-video-anta-1001.mp4',
                'proof_attachment' => 'complaints/proofs/sample-proof-anta-1001.pdf',
            ],
            [
                'source' => $sources['KAE'],
                'tanggal_complaint' => now()->subDays(4)->toDateString(),
                'tanggal_order' => now()->subDays(6)->toDateString(),
                'jam_customer_complaint' => '13:20:00',
                'brand' => $brand,
                'platform' => $platforms['SHOPEE'],
                'sku' => $skus['SKU-ANTA-BASKET-002'],
                'sub_case' => $subCases['OOS'],
                'last_step' => $lastSteps['Follow Up KAE to Brand'],
                'cs_user' => $agents['CS Escalation'],
                'complaint_power' => $powers['Hard Complaint'],
                'order_id' => 'ORD-ANTA-1002',
                'username' => 'oos.customer',
                'resi' => 'RESI-ANTA-1002',
                'qty' => 2,
                'cause_by' => 'KAE',
                'proof' => 'Lampiran bukti stok kosong dan percakapan dengan customer.',
                'summary_case' => 'Pesanan tidak dapat dipenuhi karena varian utama sedang habis.',
                'update_long_text' => 'KAE follow up ke brand untuk opsi penggantian varian dan kompensasi.',
                'part_of_bad' => 'Stock allocation',
                'step_cs_selesai' => 'NO',
                'tanggal_update' => now()->subDays(3)->toDateString(),
                'complaint_power_name' => 'Hard Complaint',
                'video_unboxing' => 'complaints/videos/sample-video-anta-1002.mp4',
                'proof_attachment' => 'complaints/proofs/sample-proof-anta-1002.png',
            ],
            [
                'source' => $sources['Brand'],
                'tanggal_complaint' => now()->subDays(2)->toDateString(),
                'tanggal_order' => now()->subDays(5)->toDateString(),
                'jam_customer_complaint' => '16:45:00',
                'brand' => $brand,
                'platform' => $platforms['TIKTOK'],
                'sku' => $skus['SKU-ANTA-LIFESTYLE-003'],
                'sub_case' => $subCases['Bad Quality Product'],
                'last_step' => $lastSteps['Claim Reject'],
                'cs_user' => $agents['Test User'],
                'complaint_power' => $powers['Hard Complaint'],
                'order_id' => 'ORD-ANTA-1003',
                'username' => 'repeat.customer',
                'resi' => 'RESI-ANTA-1003',
                'qty' => 1,
                'cause_by' => 'BRAND',
                'proof' => 'Dokumentasi foto produk dan kronologi klaim pertama customer.',
                'summary_case' => 'Customer menilai kualitas jahitan buruk, namun bukti tidak cukup mendukung klaim.',
                'update_long_text' => 'Tim melakukan validasi bukti, hasilnya tidak memenuhi syarat klaim penggantian.',
                'part_of_bad' => 'Upper stitching',
                'step_cs_selesai' => 'YES',
                'tanggal_step_cs_selesai' => now()->subDay()->toDateString(),
                'tanggal_update' => now()->subDay()->toDateString(),
                'reason_whitelist' => 'Late Respons',
                'reason_late_respons' => 'CS',
                'complaint_power_name' => 'Hard Complaint',
                'video_unboxing' => 'complaints/videos/sample-video-anta-1003.mov',
                'proof_attachment' => 'complaints/proofs/sample-proof-anta-1003.jpg',
            ],
            [
                'source' => $sources['Socmed'],
                'tanggal_complaint' => now()->toDateString(),
                'tanggal_order' => now()->subDays(2)->toDateString(),
                'jam_customer_complaint' => '09:05:00',
                'brand' => $brand,
                'platform' => $platforms['TOKOPEDIA'],
                'sku' => $skus['SKU-ANTA-LIFESTYLE-003'],
                'sub_case' => $subCases['Wrong Product'],
                'last_step' => $lastSteps['Seller Win'],
                'cs_user' => $agents['CS ANTA 1'],
                'complaint_power' => $powers['Normal Complaint'],
                'order_id' => 'ORD-ANTA-1004',
                'username' => 'repeat.customer',
                'resi' => 'RESI-ANTA-1004',
                'qty' => 1,
                'cause_by' => 'CS',
                'proof' => 'Bukti video pembukaan paket dan label pesanan sesuai.',
                'summary_case' => 'Customer mengklaim menerima produk yang salah, namun bukti internal menunjukkan produk sesuai pesanan.',
                'update_long_text' => 'CS mengirimkan bukti label dan video pengepakan, lalu kasus ditutup sebagai seller win.',
                'part_of_bad' => 'Label checking',
                'step_cs_selesai' => 'YES',
                'tanggal_step_cs_selesai' => now()->toDateString(),
                'tanggal_update' => now()->toDateString(),
                'complaint_power_name' => 'Normal Complaint',
                'video_unboxing' => 'complaints/videos/sample-video-anta-1004.mp4',
                'proof_attachment' => 'complaints/proofs/sample-proof-anta-1004.pdf',
            ],
        ];

        foreach ($items as $item) {
            $payload = $this->buildPayload($item);

            Complaint::query()->updateOrCreate(
                ['order_id' => $payload['order_id']],
                $payload,
            );
        }
    }

    private function buildPayload(array $item): array
    {
        $tanggalComplaint = Carbon::parse($item['tanggal_complaint']);
        $tanggalUpdate = Carbon::parse($item['tanggal_update']);
        $lastStep = $item['last_step'];
        $subCase = $item['sub_case'];
        $hasOosHistory = Oos::query()->where('order_id', $item['order_id'])->exists();

        $payload = [
            'source' => $item['source']->name,
            'tanggal_complaint' => $item['tanggal_complaint'],
            'tanggal_order' => $item['tanggal_order'],
            'jam_customer_complaint' => $item['jam_customer_complaint'],
            'brand' => $item['brand']->name,
            'platform' => $item['platform']->name,
            'order_id' => $item['order_id'],
            'username' => $item['username'],
            'resi' => $item['resi'],
            'sku' => $item['sku']->sku,
            'product_name' => $item['sku']->product_name,
            'qty' => $item['qty'],
            'sub_case' => $subCase->name,
            'cause_by' => $item['cause_by'],
            'proof' => $item['proof'],
            'summary_case' => $item['summary_case'],
            'update_long_text' => $item['update_long_text'],
            'part_of_bad' => $item['part_of_bad'],
            'cs_name' => $item['cs_user']->name,
            'last_step' => $lastStep->name,
            'step_cs_selesai' => $item['step_cs_selesai'],
            'tanggal_step_cs_selesai' => $item['tanggal_step_cs_selesai'] ?? null,
            'tanggal_update' => $item['tanggal_update'],
            'reason_whitelist' => $item['reason_whitelist'] ?? null,
            'reason_late_respons' => $item['reason_late_respons'] ?? null,
            'video_unboxing' => $item['video_unboxing'],
            'complaint_power' => $item['complaint_power_name'],
            'cycle' => $this->resolveCycle($item['jam_customer_complaint']),
            'status' => $lastStep->status_result,
            'priority' => $lastStep->priority_level,
            'history' => $this->resolveHistory($item['username'], $item['order_id']),
            'oos' => $hasOosHistory ? 'Ada Riwayat OOS' : null,
            'report_category' => $subCase->default_cause_by ?: $item['cause_by'],
            'auto_sync_sla' => $this->resolveAutoSyncSla($tanggalComplaint, $tanggalUpdate, $lastStep->status_result),
            'sla' => $this->resolveSla($tanggalComplaint, $tanggalUpdate, $lastStep->status_result),
            'proof_attachment' => $item['proof_attachment'],
        ];

        return $payload;
    }

    private function resolveCycle(string $jamCustomerComplaint): string
    {
        return $jamCustomerComplaint >= '21:00:00' || $jamCustomerComplaint <= '15:00:00'
            ? 'Cycle 1 (21.00 - 15.00)'
            : 'Cycle 2 (15.01 - 20.59)';
    }

    private function resolveSla(Carbon $tanggalComplaint, Carbon $tanggalUpdate, string $status): int
    {
        $endDate = $status === 'Solved' ? $tanggalUpdate : now();

        return max(0, $tanggalComplaint->diffInDays($endDate));
    }

    private function resolveAutoSyncSla(Carbon $tanggalComplaint, Carbon $tanggalUpdate, string $status): string
    {
        $sla = $this->resolveSla($tanggalComplaint, $tanggalUpdate, $status);

        return $sla <= 0 ? 'Within 1 day' : "Above {$sla} days";
    }

    private function resolveHistory(string $username, string $orderId): ?string
    {
        $orderSequence = [
            'repeat.customer' => ['ORD-ANTA-1003', 'ORD-ANTA-1004'],
        ];

        if (! isset($orderSequence[$username])) {
            return null;
        }

        $position = array_search($orderId, $orderSequence[$username], true);

        if ($position === false || $position === 0) {
            return null;
        }

        $count = $position + 1;

        return "complaint ke {$count}";
    }
}
