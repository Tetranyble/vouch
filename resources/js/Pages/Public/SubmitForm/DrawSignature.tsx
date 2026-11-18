import { Head } from "@inertiajs/react";

export default function DrawSignature({ slug }) {
  return (
    <>
      <Head title={`Signature ${slug}`} />
      <div className="p-6">
        <h1 className="text-2xl font-semibold">Draw Signature</h1>
      </div>
    </>
  );
}
