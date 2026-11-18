import { Head } from "@inertiajs/react";

export default function TemplatesEdit({ templateId }) {
  return (
    <>
      <Head title={`Edit Template ${templateId}`} />
      <div className="p-6">
        <h1 className="ui-page-title">Edit Template {templateId}</h1>
      </div>
    </>
  );
}
