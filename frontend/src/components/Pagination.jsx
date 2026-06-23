export default function Pagination({ page, pageSize, total, onPageChange }) {
  const pageCount = Math.max(1, Math.ceil(total / pageSize));
  return (
    <div className="pagination-bar">
      <button className="button secondary" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
        Previous
      </button>
      <span>Page {page} of {pageCount}</span>
      <button className="button secondary" disabled={page >= pageCount} onClick={() => onPageChange(page + 1)}>
        Next
      </button>
    </div>
  );
}
