<?php

namespace Modules\GoogleWorkspace\Http\Controllers\Api;

use Modules\Core\Http\Controllers\ApiController;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleDriveService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleDocsService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleSheetsService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleSlidesService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleFormsService;

class GoogleWorkspaceController extends ApiController
{

    public function redirectToGoogle()
    {
        $googleOauthService = new GoogleOAuthService();
        return response()->json([
            'url' => $googleOauthService->getAuthUrl()
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        $googleOauthService = new GoogleOAuthService();

        Log::error("GOOGLE WORKSPACE CONTROLLER - HANDLE GOOGLE CALLBACK ", ["req" => $request->all()]);
        return redirect($googleOauthService->handleCallback($request) ?? '/');
    }

    public function tokenStatus(Request $request)
    {
        $token = GoogleToken::where('user_id', Auth::id())->first();

        return response()->json([
            'connected' => $token !== null
        ]);
    }

    public function listDocs()
    {
        $googleDocsService = new GoogleDocsService();
        return response()->json($googleDocsService->listDocs());
    }

    public function syncDocs()
    {
        $googleDocsService = new GoogleDocsService();
        return response()->json($googleDocsService->syncDocs());
    }

    public function syncSheets()
    {
        $googleSheetsService = new GoogleSheetsService();
        return response()->json($googleSheetsService->syncSheets());
    }

    public function syncSlides()
    {
        $googleSlidesService = new GoogleSlidesService();
        return response()->json($googleSlidesService->syncSlides());
    }

    public function syncForms()
    {
        $googleFormsService = new GoogleFormsService();
        return response()->json($googleFormsService->syncForms());

    }

    public function syncGoogleDriveFiles()
    {
        $googleDriveService = new GoogleDriveService();
        return response()->json($googleDriveService->syncAllFiles());

    }

    public function listGoogleDriveFiles()
    {
        $googleDriveService = new GoogleDriveService();
        return response()->json($googleDriveService->listFiles());
    }

    public function getDocContent($docId)
    {
        $googleDocsService = new GoogleDocsService();
        return response()->json($googleDocsService->getDocContent($docId));
    }

    public function getFiles(Request $request, $fileType)
    {

        $googleDriveService = new GoogleDriveService();
        return response()->json($googleDriveService->listFileByType($fileType));

    }

    public function destroy($type,$googleId)
    {
        $googleDriveService = new GoogleDriveService();
        if ($googleDriveService->deleteDoc($type,$googleId))
            return response()->json(['message' => 'Document deleted successfully.'], 200);
        else
            return response()->json(['message' => 'Failed to delete document.'], 500);
    }



    public function proxyDocument(Request $request,$type ,$documentId)
    {
        $googleDriveService = new GoogleDriveService();
        $iframeUrl = $googleDriveService->getDocsByProxy($type,$documentId);
        return response()->json([
            'iframe_url' => $iframeUrl,
        ]);
    }

    public function iframe($id)
    {
        $editUrl = "https://docs.google.com/document/d/{$id}/edit";

        return view('googleworkspace::iframe', compact('editUrl'));
    }

    /**
     * Create a new Google file (Sheet, Doc, Slide, Form, etc.)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createGoogleFile(Request $request)
    {
        // Validation: name required for non-upload, not required for upload
        $isFileUpload = $request->hasFile('file');
        $validationRules = [
            'type' => 'required|string',
            'description' => 'nullable|string',
            'mime_type' => 'nullable|string|in:application/vnd.google-apps.spreadsheet,application/vnd.google-apps.document,application/vnd.google-apps.presentation,application/vnd.google-apps.form,application/vnd.google-apps.file'
        ];
        if ($isFileUpload) {
            $validationRules['file'] = 'required|file';
            $validationRules['name'] = 'nullable|string';
        } else {
            $validationRules['name'] = 'required|string';
        }
        $request->validate($validationRules);

        $type = strtolower($request->input('type'));
        $name = $request->input('name');
        $description = $request->input('description') ?? null;
        $mimeType = $request->input('mime_type');
        $result = null;

        switch ($type) {
            case 'sheet':
                $service = new GoogleSheetsService();
                $result = $service->createSheet($name);
                break;
            case 'doc':
                $service = new GoogleDocsService();
                $result = $service->createDoc($name);
                break;
            case 'slide':
                $service = new GoogleSlidesService();
                $result = $service->createSlide($name);
                break;
            case 'form':
                $service = new GoogleFormsService();
                $result = $service->createForm($name);
                break;
            case 'drive':
                $service = new GoogleDriveService();
                if ($request->hasFile('file')) {
                    $uploadedFile = $request->file('file');
                    $result = $service->uploadDriveFile(
                        $uploadedFile,
                        $name,
                        $description,
                        $mimeType
                    );
                } else {
                    $result = $service->createDriveFile($name, $description, $mimeType);
                }
                break;
            default:
                return response()->json(['message' => 'Invalid file type'], 400);
        }

        if (is_array($result) && isset($result['success']) && $result['success']) {
            return response()->json(['message' => 'File created successfully', 'file' => $result['file']], 201);
        } else {
            $errorMsg = (is_array($result) && isset($result['message'])) ? $result['message'] : 'Failed to create file';
            return response()->json(['message' => $errorMsg], 500);
        }
    }
}
