/* Fix grid overflow in edit forms */
.ts-grid2{
  grid-template-columns: minmax(0,1fr) minmax(0,260px) minmax(0,160px);
}
.ts-grid2 > *{ min-width: 0; }
.ts-field input[type="text"],
.ts-field input[type="number"],
.ts-field select{
  box-sizing: border-box;
  max-width: 100%;
}

/* чуть раньше ломаемся на 3 колонки */
@media (max-width: 1100px){
  .ts-grid2{ grid-template-columns: minmax(0,1fr) minmax(0,260px); }
}
@media (max-width: 980px){
  .ts-grid2{ grid-template-columns: 1fr; }
}
