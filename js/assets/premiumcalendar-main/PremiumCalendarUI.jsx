import { useEffect, useId } from "react";
import MMKPremiumCalendar from "./dist/premiumcalendar.min";
import "./dist/premiumcalendar.min.css";

function PremiumCalendarUI({ config }) {
  const id = `calendar-${useId().replace(/[:]/g, "")}`;

  useEffect(() => {
    const calendar = new MMKPremiumCalendar(`#${id}`, config);

    return () => {
      if (calendar?.destroy) {
        calendar.destroy();
      }
    };
  }, [id, config]);

  return <div id={id}></div>;
}

export default PremiumCalendarUI;
