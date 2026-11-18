import { Head } from "@inertiajs/react";

export default function TemplatesShare({ templateId }) {
  return (
    <>
      <Head title={`Share ${templateId}`} />
      <div className="p-6">
        <h1 className="ui-page-title">Share Template</h1>
      </div>
    </>
  );
}
