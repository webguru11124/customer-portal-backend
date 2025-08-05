<?php

namespace Tests\Unit\Http\Requests;

use App\Enums\Resources;
use App\Http\Requests\DownloadCustomerDocumentRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class DownloadCustomerDocumentRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->downloadDocumentRequest = new DownloadCustomerDocumentRequest();
    }

    public function test_it_authorized_the_request(): void
    {
        $this->assertTrue($this->downloadDocumentRequest->authorize());
    }

    public function test_it_has_the_correct_rules(): void
    {
        $this->assertEquals(
            $this->downloadDocumentRequest->rules(),
            [
                'documentType' => [
                    'required',
                    Rule::in([
                        Resources::DOCUMENT->value,
                        Resources::CONTRACT->value,
                        Resources::FORM->value,
                    ]),
                ],
            ]
        );
    }

    /**
     * @dataProvider provideRequestPayload
     */
    public function test_valid_data_passes_validation(
        array $payload,
        bool $expectedResult
    ): void {
        $validator = Validator::make($payload, $this->downloadDocumentRequest->rules());
        $this->assertEquals($expectedResult, $validator->passes());
    }

    private function provideRequestPayload(): iterable
    {
        yield 'correct type: Document' => [['documentType' => 'Document'], true];
        yield 'correct type: Contract' => [['documentType' => 'Contract'], true];
        yield 'correct type: Form' => [['documentType' => 'Form'], true];
        yield 'invalid type: nullable' => [['documentType' => null], false];
        yield 'invalid type: empty string' => [['documentType' => ''], false];
        yield 'invalid type: number' => [['documentType' => 12345], false];
        yield 'invalid type' => [['documentType' => 'Document1'], false];
    }
}
