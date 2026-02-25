<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\AuthHelper;
use App\Models\QueueLabelPrint;
use App\Models\RackPartList;
use Illuminate\Http\Request;

class LabelControlller extends Controller
{
    use AuthHelper;

    public function index()
    {
        $user = $this->getUser();
        // debugbar()->info($user->Name_Member);
        // Only show non-printed/pending labels
        $labels = QueueLabelPrint::where('requested_by', $user->Name_Member)->where('printed',false)
            ->orderBy('urgent', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
        $urlLabel = config('app.label');
        return view('users.label.index', compact('labels','urlLabel'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Label_Name' => 'required|string|max:255',
            'Quantity' => 'required|integer|min:1',
            'Area' => 'required|string',
            'Jenis' => 'required|string',
            'urgent' => 'nullable|boolean',
        ], [
            'Label_Name.required' => 'Nama Label wajib diisi',
            'Quantity.required' => 'Quantity wajib diisi',
            'Quantity.min' => 'Quantity minimal 1',
            'Area.required' => 'Area wajib dipilih',
            'Jenis.required' => 'Jenis label wajib dipilih',
        ]);

        $user = $this->getUser();
        // debugbar()->info('User Info:', ['user' => $user]);
        $userName = $user->Name_Member ?? $user->Name_User ?? 'Unknown';

        try {
            QueueLabelPrint::create([
                'rack_code' => strtoupper(str_replace(' ', '', $request->Label_Name)),
                'label_type' => $request->Jenis,
                'quantity' => $request->Quantity,
                'requested_by' => $userName,
                'area_name' => $request->Area,
                'urgent' => $request->has('urgent') && (bool)$request->urgent,
            ]);

            return redirect()->route('member.label.index')
                ->with('success', 'Label request berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat label request: ' . $e->getMessage());
        }
    }

    /**
     * Print Now - Process all pending labels to auto print queue
     */
    public function printNow(Request $request)
    {
        try {
            $user = $this->getUser();
            $userName = $user->Name_Member ?? $user->Name_User ?? 'Unknown';

            // Get all pending labels
            $pendingLabels = QueueLabelPrint::where('printed', 'pending')
                ->orWhereNull('printed')
                ->get();

            if ($pendingLabels->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada label pending untuk diprint'
                ], 400);
            }

            $processedCount = 0;
            $processedIds = [];

            foreach ($pendingLabels as $label) {
                // Update status to queued for auto print
                $label->update([
                    'auto_print' => true,
                    'requested_at' => now(),
                ]);

                $processedIds[] = $label->id;
                $processedCount++;
            }

            // Here you can trigger the actual auto print service/API
            // Example: dispatch job to printer service
            // AutoPrintJob::dispatch($processedIds);

            return response()->json([
                'success' => true,
                'message' => "{$processedCount} label berhasil dikirim ke auto print queue",
                'processed_count' => $processedCount,
                'label_ids' => $processedIds
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses auto print: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchRackPart(Request $request)
    {
        $query = $request->input('query', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $results = RackPartList::where(function($q) use ($query) {
            $q->where('rack_no', 'LIKE', '%' . $query . '%');
        })
            ->orderBy('rack_no')
            ->limit(10)
            ->get(['id', 'rack_no', 'part_name', 'item_code']);

        return response()->json($results);
    }
}
