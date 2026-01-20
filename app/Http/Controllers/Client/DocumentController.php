<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Document;

class DocumentController extends Controller
{
    /**
     * Listar documentos del cliente
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        // Si no tiene perfil, retornar colección vacía
        if (!$profile) {
            return Inertia::render('Client/Documents/Index', [
                'documents' => [],
            ]);
        }
        
        $documents = Document::where('documentable_type', 'App\Models\Profile')
            ->where('documentable_id', $profile->id)
            ->orderByDesc('created_at')
            ->paginate(20) ?? collect();
        
        return Inertia::render('Client/Documents/Index', [
            'documents' => $documents,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10 MB
            'name' => 'required|string|max:255',
            'file_type' => 'required|in:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,other',
        ]);
        
        $user = $request->user();
        $profile = $user->profile;
        
        if (!$profile) {
            return back()->withErrors(['error' => 'Perfil no configurado. Por favor completa tu perfil primero.']);
        }
        
        try {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('documents/' . $profile->id, $filename, 'public');
            
            Document::create([
                'documentable_type' => 'App\Models\Profile',
                'documentable_id' => $profile->id,
                'name' => $validated['name'],
                'file_path' => $path,
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'file_type' => $validated['file_type'],
                'uploaded_by' => $user->id,
            ]);
            
            return back()->with('message', 'Documento subido correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al subir documento: ' . $e->getMessage()]);
        }
    }
    
    public function destroy(Request $request, Document $document)
    {
        $user = $request->user();
        $profile = $user->profile;
        
        if (!$profile) {
            abort(403, 'Perfil no encontrado');
        }
        
        // Verificar que sea el documento del usuario
        if ($document->documentable_id !== $profile->id || $document->documentable_type !== 'App\Models\Profile') {
            abort(403);
        }
        
        try {
            // Eliminar archivo
            if ($document->file_path) {
                \Storage::disk('public')->delete($document->file_path);
            }
            
            // Eliminar registro
            $document->delete();
            
            return back()->with('message', 'Documento eliminado correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar documento: ' . $e->getMessage()]);
        }
    }
}
