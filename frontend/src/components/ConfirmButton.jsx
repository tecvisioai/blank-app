export default function ConfirmButton({ onConfirm, label = 'Delete', confirmText = 'Are you sure?', className = '', ...props }) {
  const handleClick = () => {
    if (window.confirm(confirmText)) {
      onConfirm();
    }
  };
  return (
    <button type="button" className={`button danger ${className}`} onClick={handleClick} {...props}>
      {label}
    </button>
  );
}
