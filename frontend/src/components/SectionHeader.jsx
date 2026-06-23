export default function SectionHeader({ title, subtitle, children }) {
  return (
    <div className="section-header">
      <div>
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      <div>{children}</div>
    </div>
  );
}
