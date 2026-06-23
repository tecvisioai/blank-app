export default function Badge({ label, type = 'default' }) {
  return <span className={`badge badge-${type.toLowerCase()}`}>{label}</span>;
}
